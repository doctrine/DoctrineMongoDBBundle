<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use MongoDB\Client;
use MongoDB\Driver\Command;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\Server;

use function array_flip;
use function array_intersect_key;
use function iterator_count;
use function version_compare;

/** @internal */
class ConnectionDiagnostic
{
    private const CLIENT_ENCRYPTION_OPTION_NAMES = [
        'keyVaultClient',
        'keyVaultNamespace',
        'kmsProviders',
        'tlsOptions',
    ];

    public function __construct(
        private readonly Client $client,
        private readonly array $driverOptions,
    ) {
    }

    /**
     * Get the list of auto encryption providers configured for the MongoDB client
     * and an indication of whether the configuration is valid.
     *
     * @return array{autoEncryptionEnabled: bool, keyVaultNamespace: string, keyCount: int}|null
     */
    public function getAutoEncryptionInfo(): ?array
    {
        if (! isset($this->driverOptions['autoEncryption'])) {
            return null;
        }

        $autoEncryption = $this->driverOptions['autoEncryption'];

        $clientEncryptionOpts = array_intersect_key($autoEncryption, array_flip(self::CLIENT_ENCRYPTION_OPTION_NAMES));
        $clientEncryption     = $this->client->createClientEncryption($clientEncryptionOpts);

        return [
            'autoEncryptionEnabled' => true,
            'keyVaultNamespace' => $autoEncryption['keyVaultNamespace'],
            'keyCount' => iterator_count($clientEncryption->getKeys()),
        ];
    }

    /** @return array{topologyName: string, topologySupported: bool, version: ?string, versionSupported: bool} */
    public function getServerInfo(): array
    {
        $server    = $this->client->getManager()->selectServer(new ReadPreference(ReadPreference::PRIMARY_PREFERRED));
        $buildInfo = $server->executeCommand('admin', new Command(['buildInfo' => 1]))->toArray()[0] ?? null;

        $version = $buildInfo->version ?? null;

        return [
            'topologyName' => $this->getTopologyType($server),
            'topologySupported' => $server->getType() !== Server::TYPE_STANDALONE,
            'version' => $version,
            'versionSupported' => $version ? version_compare($version, '7.0.0', '>=') : false,
        ];
    }

    public function usesAutoEncryption(): bool
    {
        return isset($this->driverOptions['autoEncryption']);
    }

    private function getTopologyType(Server $server): string
    {
        return match ($server->getType()) {
            Server::TYPE_STANDALONE => 'Standalone',
            Server::TYPE_MONGOS => 'Sharded Cluster',
            Server::TYPE_RS_PRIMARY,
            Server::TYPE_RS_SECONDARY,
            Server::TYPE_RS_ARBITER => 'Replica Set',
            default => 'Unknown',
        };
    }
}
