<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command\Encryption;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use MongoDB\BSON\PackedArray;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Dumper;

use function assert;
use function json_decode;
use function json_encode;
use function sprintf;
use function var_export;

use const JSON_BIGINT_AS_STRING;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/** @internal */
#[AsCommand(
    name: 'doctrine:mongodb:encryption:dump-fields-map',
    description: 'Dumps the encrypted fields map for all documents in the configured connections.',
)]
final class DumpFieldsMapCommand extends Command
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            'The output format for the encrypted fields map (yaml, json, php)',
            'yaml',
            ['yaml', 'php', 'json']
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $format = $input->getOption('format');

        $dumper = new Dumper();

        foreach ($this->registry->getManagers() as $name => $documentManager) {
            assert($documentManager instanceof DocumentManager);
            $encryptedFieldsMap = [];
            foreach ($documentManager->getMetadataFactory()->getAllMetadata() as $metadata) {
                $database               = $documentManager->getDocumentDatabase($metadata->getName());
                $collectionInfoIterator = $database->listCollections(['filter' => ['name' => $metadata->getCollection()]]);

                foreach ($collectionInfoIterator as $collectionInfo) {
                    if ($collectionInfo['options']['encryptedFields'] ?? null) {
                        $encryptedFieldsMap[$this->getDocumentNamespace($metadata, $database->getDatabaseName())] = $collectionInfo['options']['encryptedFields'];
                    }
                }
            }

            if (empty($encryptedFieldsMap)) {
                continue;
            }

            // The min/max query options must have the same type as the field.
            // But the PHP driver always convert to "int" or "float" when the value fit in the range
            foreach ($encryptedFieldsMap as $ns => $encryptedFields) {
                $fields = json_decode(PackedArray::fromPHP($encryptedFields['fields'])->toCanonicalExtendedJSON(), true, flags: JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
                foreach ($fields as &$field) {
                    if ($field['bsonType'] === 'long') {
                        if (isset($field['queries']['min']['$numberInt'])) {
                            $field['queries']['min'] = ['$numberLong' => $field['queries']['min']['$numberInt']];
                        }

                        if (isset($field['queries']['max']['$numberInt'])) {
                            $field['queries']['max'] = ['$numberLong' => $field['queries']['max']['$numberInt']];
                        }
                    } elseif ($field['bsonType'] === 'decimal') {
                        if (isset($field['queries']['min']['$numberDouble'])) {
                            $field['queries']['min'] = ['$numberDecimal' => $field['queries']['min']['$numberDouble']];
                        }

                        if (isset($field['queries']['max']['$numberDouble'])) {
                            $field['queries']['max'] = ['$numberDecimal' => $field['queries']['max']['$numberDouble']];
                        }
                    }
                }

                // Keep only the "fields" key and ignore "escCollection" and "ecocCollection"
                $encryptedFieldsMap[$ns] = ['fields' => $fields];
            }

            $io->section(sprintf('Dumping encrypted fields map for document manager "%s"', $name));
            switch ($format) {
                case 'yaml':
                    $outputContent = $dumper->dump($encryptedFieldsMap, 3);
                    break;
                case 'php':
                    $outputContent = var_export($encryptedFieldsMap, true);
                    break;
                case 'json':
                    $outputContent = json_encode($encryptedFieldsMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    break;
                default:
                    $io->error(sprintf('Unknown format "%s"', $format));

                    return Command::FAILURE;
            }

            $output->writeln($outputContent, OutputInterface::VERBOSITY_QUIET);
        }

        return Command::SUCCESS;
    }

    private function getDocumentNamespace(ClassMetadata $metadata, string $defaultDb): string
    {
        $db = $metadata->getDatabase() ?: $defaultDb ?: 'doctrine';

        return $db . '.' . $metadata->getCollection();
    }
}
