<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\DataCollector\ConnectionDiagnostic;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Throwable;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/** @internal */
#[AsCommand(
    name: 'doctrine:mongodb:connection:diagnostic',
    description: 'Diagnose MongoDB configuration and server capabilities for each connection.',
)]
final class ConnectionDiagnosticCommand extends Command
{
    /** @param ServiceProviderInterface<ConnectionDiagnostic> $diagnostics */
    public function __construct(private readonly ServiceProviderInterface $diagnostics)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('connection', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The name of the connection to diagnose. If not specified, all connections will be diagnosed.', [], $this->getConnectionNames(...));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('MongoDB Encryption Diagnostics');

        /** @var string[] $connectionNames */
        $connectionNames = $input->getOption('connection');
        if ($connectionNames) {
            if (array_diff($connectionNames, $this->getConnectionNames())) {
                $io->error('One or more specified connections do not exist. Available connections: ' . implode(', ', $this->getConnectionNames()));

                return Command::INVALID;
            }
        } else {
            $connectionNames = $this->getConnectionNames();
        }

        foreach ($connectionNames as $name) {
            $diagnostic = $this->diagnostics->get($name);
            $io->section(sprintf('Connection: %s', $name));

            $io->text('<info>PHP Environment</info>');
            try {
                $phpInfo = $diagnostic->getPhpExtensionInfo();
                $io->listing([
                    'ext-mongodb loaded: ' . ($phpInfo['ext-mongodb loaded'] ? 'Yes' : 'No'),
                    'ext-mongodb version: ' . ($phpInfo['ext-mongodb version'] ?: '[unknown]'),
                    'library version: ' . ($phpInfo['library version'] ?: '[unknown]'),
                ]);
            } catch (Throwable $exception) {
                $io->error('Could not retrieve PHP extension info: ' . $exception->getMessage());
            }

            $io->text('<info>Server Information</info>');
            try {
                $serverInfo = $diagnostic->getServerInfo();
                $io->listing([
                    'MongoDB Version: ' . ($serverInfo['version'] ?? '[unknown]'),
                    'Modules: ' . (isset($serverInfo['modules']) ? implode(', ', $serverInfo['modules']) : '[unknown]'),
                    'crypt_shared version: ' . ($serverInfo['crypt_shared_version'] ?? '[unknown]'),
                    'crypt_shared path: ' . ($serverInfo['crypt_shared_path'] ?? '[unknown]'),
                    'Topology: ' . ($serverInfo['topology'] ?? '[unknown]'),
                ]);
            } catch (Throwable $exception) {
                $io->error('Could not retrieve server info: ' . $exception->getMessage());
            }

            $io->text('<info>Auto Encryption Configuration</info>');
            try {
                $autoEncryptionInfo = $diagnostic->getAutoEncryptionInfo();
                if ($autoEncryptionInfo) {
                    $io->listing([
                        'Auto Encryption Enabled: ' . ($autoEncryptionInfo['autoEncryption enabled'] ? 'Yes' : 'No'),
                        'Key Vault Namespace: ' . $autoEncryptionInfo['keyVaultNamespace'],
                        'Key Count: ' . $autoEncryptionInfo['keyCount'],
                    ]);
                } else {
                    $io->text('No auto encryption configuration found for this connection.');
                }
            } catch (Throwable $exception) {
                $io->error('Could not retrieve auto encryption info: ' . $exception->getMessage());
            }

            $mongocryptdVersion = $diagnostic->getMongocryptdVersion();
            if ($mongocryptdVersion) {
                $io->text('<info>mongocryptd Version</info>');
                $io->text($mongocryptdVersion);
            } else {
                $io->text('mongocryptd not found');
            }
        }

        return Command::SUCCESS;
    }

    /** @return list<string> */
    private function getConnectionNames(): array
    {
        return array_keys($this->diagnostics->getProvidedServices());
    }
}
