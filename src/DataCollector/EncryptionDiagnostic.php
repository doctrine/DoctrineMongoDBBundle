<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Composer\InstalledVersions;
use ReflectionExtension;

use function escapeshellarg;
use function exec;
use function explode;
use function extension_loaded;
use function file_exists;
use function getenv;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function phpversion;
use function preg_match;
use function preg_quote;
use function sprintf;
use function trim;

/** @internal */
class EncryptionDiagnostic
{
    /** @return array{extensionLoaded: bool, extensionVersion: ?string, extensionSupportsLibmongocrypt: bool, libraryVersion: ?string} */
    public function getPhpExtensionInfo(): array
    {
        // There will be no "libmongocrypt" entry unless libmongocrypt is not available.
        // When ext-mongodb was compiled with libmongocrypt support, either "libmongocrypt bundled version"
        // or "libmongocrypt library version" will be available instead
        $libmongocryptAvailable = $this->getExtensionInfoRow('libmongocrypt') !== 'disabled';

        return [
            'extensionLoaded' => extension_loaded('mongodb'),
            'extensionVersion' => phpversion('mongodb') ?: null,
            'extensionSupportsLibmongocrypt' => $libmongocryptAvailable,
            'libraryVersion' => InstalledVersions::getPrettyVersion('mongodb/mongodb'),
        ];
    }

    /** @return array{mongocryptdPath: ?string, mongocryptdVersion: ?string} */
    public function getMongocryptdInfo(): array
    {
        $mongocryptdPath = $this->findMongocryptdPath();

        return [
            'mongocryptdPath' => $mongocryptdPath,
            'mongocryptdVersion' => $this->getMongocryptdVersion($mongocryptdPath),
        ];
    }

    private function findMongocryptdPath(): ?string
    {
        $paths = explode(':', getenv('PATH') ?: '');

        foreach ($paths as $path) {
            if (file_exists($path . '/mongocryptd')) {
                return $path . '/mongocryptd';
            }
        }

        return null;
    }

    private function getMongocryptdVersion(?string $mongocryptdPath): ?string
    {
        if ($mongocryptdPath === null) {
            return null;
        }

        $output = [];
        exec(escapeshellarg($mongocryptdPath) . ' --version', $output);

        if (isset($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    private function getExtensionInfo(): string
    {
        $extension = new ReflectionExtension('mongodb');

        ob_start();
        $extension->info();
        $info = ob_get_contents();
        ob_end_clean();

        return (string) $info;
    }

    private function getExtensionInfoRow(string $row): ?string
    {
        $pattern = sprintf('/^%s(.*)$/m', preg_quote($row . ' => '));

        if (preg_match($pattern, $this->getExtensionInfo(), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
