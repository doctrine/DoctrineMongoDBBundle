<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use PharData;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

use function addslashes;
use function call_user_func;
use function class_exists;
use function php_uname;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;

/** @internal */
#[AsCommand(
    name: 'app:mongodb:install-libmongocrypt',
    description: 'Installs the libmongocrypt shared library for Client-Side Field Level Encryption.',
)]
class InstallLibmongocryptCommand extends Command
{
    private const LIBMONOGCRYPT_VERSION = '1.8.0'; // You might want to make this configurable or fetch the latest
    private const DOWNLOAD_BASE_URL     = 'https://fastdl.mongodb.org/crypt_shared/';
    private const LICENSE_URL           = 'https://www.mongodb.com/legal/terms/licenses/sspl'; // MongoDB SSPL License

    private Filesystem $filesystem;
    private HttpClientInterface $httpClient;
    private string $tempDir;
    /** @var callable */
    private $uname;

    /**
     * @param Filesystem|null          $filesystem The Filesystem component.
     * @param HttpClientInterface|null $httpClient The HTTP client for downloads.
     * @param string|null              $tempDir    Directory for temporary downloads. Defaults to sys_get_temp_dir().
     * @param callable|null            $uname      Callable for php_uname, defaults to the built-in function.
     */
    public function __construct(
        ?Filesystem $filesystem = null,
        ?HttpClientInterface $httpClient = null,
        ?string $tempDir = null,
        ?callable $uname = null,
    ) {
        parent::__construct();

        if (! class_exists(HttpClient::class)) {
            throw new RuntimeException('The Symfony HttpClient component is required to run this command. Please install it via "composer require symfony/http-client".');
        }

        $this->filesystem = $filesystem ?? new Filesystem();
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->tempDir    = $tempDir ?? sys_get_temp_dir();
        $this->uname      = $uname ?? php_uname();
    }

    protected function configure(): void
    {
        $this
            ->addOption('accept-license', null, InputOption::VALUE_NONE, 'Accepts the MongoDB SSPL license without prompt.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $acceptLicense = $input->getOption('accept-license');

        if (! $acceptLicense) {
            $io->warning('The libmongocrypt library is distributed under the MongoDB Server Side Public License (SSPL).');
            $io->text(sprintf('You can review the license terms here: %s', self::LICENSE_URL));
            $io->newLine();

            if (! $io->confirm('Do you accept the terms of the MongoDB SSPL license to proceed with the installation?', false)) {
                $io->error('License not accepted. Aborting installation.');

                return Command::FAILURE;
            }
        } else {
            $io->info('MongoDB SSPL license accepted via --accept-license option.');
        }

        $io->title('Installing libmongocrypt for MongoDB CSFLE');

        // Determine OS and architecture using the injected callable
        $os   = strtolower(PHP_OS);
        $arch = call_user_func($this->uname, 'm'); // machine architecture

        $filename = $this->getLibmongocryptFilename($os, $arch);
        if (! $filename) {
            $io->error(sprintf('Unsupported operating system (%s) or architecture (%s).', $os, $arch));

            return Command::FAILURE;
        }

        $downloadUrl  = self::DOWNLOAD_BASE_URL . $filename;
        $downloadPath = $this->tempDir . DIRECTORY_SEPARATOR . $filename;
        $installDir   = $this->getInstallDirectory($os);

        if (! $installDir) {
            $io->error(sprintf('Could not determine a suitable installation directory for %s.', $os));

            return Command::FAILURE;
        }

        // Ensure the installation directory exists
        if (! $this->filesystem->exists($installDir)) {
            $io->note(sprintf('Creating installation directory: %s', $installDir));
            $this->filesystem->mkdir($installDir);
        }

        $io->text(sprintf('Attempting to download %s from %s', $filename, $downloadUrl));
        $io->text(sprintf('Saving to temporary path: %s', $downloadPath));

        try {
            $response = $this->httpClient->request('GET', $downloadUrl, ['timeout' => 300]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new RuntimeException(sprintf('Failed to download file. HTTP status code: %d', $statusCode));
            }

            $fileContent = $response->getContent();
            $this->filesystem->dumpFile($downloadPath, $fileContent);

            $io->success(sprintf('Successfully downloaded %s', $filename));

            // Extract the library if it's a tar.gz (Linux/macOS) or zip (Windows)
            if (str_contains($filename, '.tar.gz')) {
                $io->text('Extracting .tar.gz archive...');
                // Ensure phar extension is enabled
                if (! class_exists(PharData::class)) {
                    throw new RuntimeException('Phar extension is required for .tar.gz extraction. Please enable it in php.ini.');
                }

                $phar = new PharData($downloadPath);
                // Extract to a subdirectory to avoid polluting installDir directly with archive contents
                $extractedSubDir = $installDir . DIRECTORY_SEPARATOR . str_replace(['.tar.gz', '.tgz'], '', $filename);
                if (! $this->filesystem->exists($extractedSubDir)) {
                    $this->filesystem->mkdir($extractedSubDir);
                }

                $phar->extractTo($extractedSubDir, null, true); // Extract all to installDir, overwrite existing
                $extractedLibPath = $extractedSubDir . DIRECTORY_SEPARATOR . $this->getLibFileName($os);
            } elseif (str_contains($filename, '.zip')) {
                $io->text('Extracting .zip archive...');
                if (! class_exists(ZipArchive::class)) {
                    throw new RuntimeException('Zip extension is required for .zip extraction. Please enable it in php.ini.');
                }

                $zip = new ZipArchive();
                if ($zip->open($downloadPath) !== true) {
                    throw new RuntimeException('Failed to open zip archive.');
                }

                // Extract to a subdirectory
                $extractedSubDir = $installDir . DIRECTORY_SEPARATOR . str_replace('.zip', '', $filename);
                if (! $this->filesystem->exists($extractedSubDir)) {
                    $this->filesystem->mkdir($extractedSubDir);
                }

                $zip->extractTo($extractedSubDir);
                $zip->close();
                $extractedLibPath = $extractedSubDir . DIRECTORY_SEPARATOR . $this->getLibFileName($os);
            } else {
                // If it's just the file, move it directly
                $io->text('Moving the downloaded file directly...');
                $this->filesystem->rename($downloadPath, $installDir . DIRECTORY_SEPARATOR . $filename);
                $extractedLibPath = $installDir . DIRECTORY_SEPARATOR . $filename;
            }

            // Verify the library exists after extraction/move
            if (! $extractedLibPath || ! $this->filesystem->exists($extractedLibPath)) {
                $io->error('Failed to find the extracted libmongocrypt file after processing.');

                return Command::FAILURE;
            }

            $io->success(sprintf('libmongocrypt installed successfully at: %s', $extractedLibPath));
            $io->note('Remember to configure your MongoDB connection to use this path for Client-Side Field Level Encryption (CSFLE).');
            $io->text('For the Doctrine MongoDB ODM Bundle, you will need to configure the MongoDB client options, for example, in your `doctrine_mongodb.yaml` or a service definition.');
            $io->text('Example configuration for the MongoDB driver (not directly ODM):');
            $io->text(sprintf("    \$client = new MongoDB\\Client('mongodb://localhost:27017', [], ['autoEncryption' => ['extraOptions' => ['cryptSharedLibPath' => '%s']]]);", addslashes($extractedLibPath)));
        } catch (ExceptionInterface $e) {
            $io->error(sprintf('An error occurred during download: %s', $e->getMessage()));

            return Command::FAILURE;
        } catch (RuntimeException $e) {
            $io->error(sprintf('An error occurred during file processing: %s', $e->getMessage()));

            return Command::FAILURE;
        } finally {
            // Clean up the temporary download file
            if ($this->filesystem->exists($downloadPath)) {
                $this->filesystem->remove($downloadPath);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Determines the filename of the libmongocrypt archive based on OS and architecture.
     * This needs to be kept up-to-date with MongoDB's distribution.
     */
    private function getLibmongocryptFilename(string $os, string $arch): ?string
    {
        $version = self::LIBMONOGCRYPT_VERSION;

        return match ($os) {
            'linux' => sprintf('crypt_shared-%s-linux-x86_64.tgz', $version),
            'darwin' => sprintf('crypt_shared-%s-darwin-x64.tgz', $version),
            'winnt' => sprintf('crypt_shared-%s-windows-x86_64.zip', $version),
        };
    }

    /**
     * Determines the expected name of the shared library file itself after extraction.
     */
    private function getLibFileName(string $os): string
    {
        return match ($os) {
            'linux' => 'libmongocrypt.so',
            'darwin' =>'libmongocrypt.dylib',
            'winnt' => 'mongocrypt.dll',
            // Fallback, though getLibmongocryptFilename should prevent reaching here
            default => 'libmongocrypt',
        };
    }

    /**
     * Determines a suitable default installation directory based on the OS.
     */
    private function getInstallDirectory(string $os): ?string
    {
        // For production, you might want to consider a project-specific 'var/libmongocrypt'
        // or a system-wide directory that is guaranteed to be writable and permanent.
        // For simplicity in this example and general testing, we use a temp dir.
        return $this->tempDir . DIRECTORY_SEPARATOR . 'libmongocrypt';
    }
}
