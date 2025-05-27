<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Composer\InstalledVersions;
use MongoDB\Client;
use MongoDB\Driver\Command;
use MongoDB\Driver\ReadPreference;

use function extension_loaded;
use function phpversion;

class ConnectionDiagnostic
{
    public function __construct(private Client $client, private array $driverOptions)
    {
    }

    public function getServerInfo(): array
    {
        $server       = $this->client->getManager()->selectServer(new ReadPreference(ReadPreference::PRIMARY));
        $buildInfo    = $server->executeCommand('admin', new Command(['buildInfo' => 1]))->toArray()[0] ?? null;
        $serverStatus = $server->executeCommand('admin', new Command(['serverStatus' => 1]))->toArray()[0] ?? null;

        return [
            'version' => $buildInfo->version ?? null,
            'modules' => $buildInfo->modules ?? null,
            'crypt_shared_version' => $serverStatus->crypt_shared ?? null,
            'crypt_shared_path' => $serverStatus->crypt_shared_path ?? null,
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
}
