<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command\Encryption;

use Doctrine\Bundle\MongoDBBundle\DataCollector\ConnectionDiagnostic;
use Doctrine\Bundle\MongoDBBundle\DataCollector\EncryptionDiagnostic;
use MongoDB\Driver\Exception\RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/** @internal */
#[AsCommand(
    name: 'doctrine:mongodb:encryption:diagnostic',
    description: 'Diagnose MongoDB configuration and server capabilities for each connection.',
)]
final class DiagnosticCommand extends Command
{
    /** @param ServiceProviderInterface<ConnectionDiagnostic> $diagnostics */
    public function __construct(
        private readonly ServiceProviderInterface $diagnostics,
        private readonly EncryptionDiagnostic $encryptionDiagnostic = new EncryptionDiagnostic(),
    ) {
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

        $configOk = $this->printAndCheckExtensionInfo($io);
        $this->printMongocryptdInfo($io);

        foreach ($connectionNames as $name) {
            $diagnostic = $this->diagnostics->get($name);
            $configOk   = $this->printAndCheckConnectionDiagnostic($name, $diagnostic, $io) && $configOk;
        }

        if ($configOk) {
            $io->success('System looks ok for encryption support.');
        } else {
            $io->warning('Not all requirements for encryption support are met. Please check the diagnostics above.');
        }

        return Command::SUCCESS;
    }

    /** @return list<string> */
    private function getConnectionNames(): array
    {
        return array_keys($this->diagnostics->getProvidedServices());
    }

    /** @return bool True if the server is compatible with auto-encryption configuration, false otherwise. */
    private function printAndCheckConnectionDiagnostic(string $name, ConnectionDiagnostic $diagnostic, SymfonyStyle $io): bool
    {
        $io->section(sprintf('Connection: %s', $name));

        $autoEncryptionEnabled = $this->printAutoEncryptionConfiguration($io, $diagnostic);

        if (! $autoEncryptionEnabled) {
            return true;
        }

        return $this->printAndCheckServerInfo($io, $diagnostic);
    }

    /** @return bool True if the driver supports auto-encryption, false otherwise */
    private function printAndCheckExtensionInfo(SymfonyStyle $io): bool
    {
        $io->text('<info>PHP Environment</info>');
        $phpInfo = $this->encryptionDiagnostic->getPhpExtensionInfo();
        $io->listing([
            'MongoDB extension loaded: ' . ($phpInfo['extensionLoaded'] ? 'Yes' : 'No'),
            'MongoDB extension version: ' . ($phpInfo['extensionVersion'] ?: '[unknown]'),
            'MongoDB extension supports libmongocrypt: ' . ($phpInfo['extensionSupportsLibmongocrypt'] ? 'Yes' : 'No'),
            'MongoDB library version: ' . ($phpInfo['libraryVersion'] ?: '[unknown]'),
        ]);

        $extensionOk = $phpInfo['extensionLoaded'] && $phpInfo['extensionSupportsLibmongocrypt'];

        if (! $extensionOk) {
            $io->warning('At least one extension requirement is not met. Encryption may not work.');
        }

        return $extensionOk;
    }

    private function printMongocryptdInfo(SymfonyStyle $io): void
    {
        $io->text('<info>mongocryptd information</info>');
        $mongocryptdInfo = $this->encryptionDiagnostic->getMongocryptdInfo();

        if ($mongocryptdInfo['mongocryptdPath'] === null) {
            $io->listing(['mongocryptd: not found']);
        } else {
            $io->listing([
                'mongocryptd path: ' . $mongocryptdInfo['mongocryptdPath'],
                'mongocryptd version: ' . ($mongocryptdInfo['mongocryptdVersion'] ?: '[unknown]'),
            ]);
        }
    }

    /** @return bool True if the server supports auto-encryption, false otherwise */
    private function printAndCheckServerInfo(SymfonyStyle $io, ConnectionDiagnostic $diagnostic): bool
    {
        $io->text('<info>Server Information</info>');
        $serverInfo = $diagnostic->getServerInfo();

        $io->listing([
            'Server Version: ' . ($serverInfo['version'] ?? '[unknown]'),
            'Topology: ' . $serverInfo['topologyName'],
        ]);

        if (! $serverInfo['versionSupported']) {
            $io->warning('This server version does not support encryption.');
        }

        if (! $serverInfo['topologySupported']) {
            $io->warning('This topology does not support encryption.');
        }

        return $serverInfo['versionSupported'] && $serverInfo['topologySupported'];
    }

    /** @return bool True if the connection uses auto encryption, false otherwise. */
    private function printAutoEncryptionConfiguration(SymfonyStyle $io, ConnectionDiagnostic $diagnostic): bool
    {
        $io->text('<info>Auto Encryption Configuration</info>');
        if (! $diagnostic->usesAutoEncryption()) {
            $io->text('Auto encryption is not enabled for this connection.');

            return false;
        }

        try {
            $autoEncryptionInfo = $diagnostic->getAutoEncryptionInfo();

            $io->listing([
                'Auto Encryption Enabled: ' . ($autoEncryptionInfo['autoEncryptionEnabled'] ? 'Yes' : 'No'),
                'Key Vault Namespace: ' . $autoEncryptionInfo['keyVaultNamespace'],
                'Key Count: ' . $autoEncryptionInfo['keyCount'],
            ]);
        } catch (RuntimeException $e) {
            // We typically get an error when mongocryptd is not running or not reachable.
            $io->error('Failed to retrieve auto encryption information: ' . $e->getMessage());
        }

        return true;
    }
}
