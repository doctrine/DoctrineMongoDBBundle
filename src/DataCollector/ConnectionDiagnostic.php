<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Composer\InstalledVersions;
use MongoDB\Client;
use MongoDB\Driver\Command;
use MongoDB\Driver\ReadPreference;

use function dd;
use function exec;
use function explode;
use function extension_loaded;
use function file_exists;
use function getenv;
use function phpversion;
use function trim;

// Check if mongocryptd (enterprise installed locally) or crypt_shared is available
// Install openssl
class ConnectionDiagnostic
{
    public function __construct(private Client $client, private array $driverOptions)
    {
    }

    public function getServerInfo(): array
    {
        $server    = $this->client->getManager()->selectServer(new ReadPreference(ReadPreference::PRIMARY));
        $buildInfo = $server->executeCommand('admin', new Command(['buildInfo' => 1]))->toArray()[0] ?? null;
        // command not supported for auto encryption: serverStatus
        // $serverStatus = $server->executeCommand('admin', new Command(['serverStatus' => 1]))->toArray()[0] ?? null;

        return [
            'version' => $buildInfo->version ?? null,
            'modules' => $buildInfo->modules ?? null,
            //'crypt_shared_version' => $serverStatus->crypt_shared ?? null,
            //'crypt_shared_path' => $serverStatus->crypt_shared_path ?? null,
            'topology' => $server->getType() ?? null,
        ];
    }

    public function getPhpExtensionInfo(): array
    {
        return [
            'ext-mongodb loaded' => extension_loaded('mongodb'),
            'ext-mongodb version' => phpversion('mongodb') ?: null,
            'library version' => InstalledVersions::getPrettyVersion('mongodb/mongodb'),
        ];
    }

    /**
     * Get the list of auto encryption providers configured for the MongoDB client
     * and an indication of whether the configuration is valid.
     */
    public function getAutoEncryptionInfo(): ?array
    {
        if (! isset($this->driverOptions['autoEncryption'])) {
            return null;
        }

        $autoEncryption = $this->driverOptions['autoEncryption'];

        // Check if the "keyVaultNamespace" collection exists and is properly formatted
        $keyVaultNamespace = explode('.', $autoEncryption['keyVaultNamespace'], 2);
        $keyCount          = $this->client->getCollection($keyVaultNamespace[0], $keyVaultNamespace[1])->countDocuments();
        $clientEncryption  = $this->client->createClientEncryption([]);
        $clientEncryption->getKeys();
        dd($clientEncryption->getKeys());

        return [
            'autoEncryption enabled' => true,
            'keyVaultNamespace' => $autoEncryption['keyVaultNamespace'],
            'keyCount' => $keyCount,
        ];
    }

    public function getMongocryptdVersion(): ?string
    {
        $mongocryptdPath = $this->findMongocryptdPath();
        if ($mongocryptdPath === null) {
            return null;
        }

        $output = [];
        exec($mongocryptdPath . ' --version', $output);

        if (isset($output[0])) {
            return trim($output[0]);
        }

        return null;
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
}
