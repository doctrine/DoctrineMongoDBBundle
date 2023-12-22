<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to clear the metadata cache of the various cache drivers.
 *
 * @internal
 */
final class ClearMetadataCacheDoctrineODMCommand extends MetadataCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:cache:clear-metadata')
            ->setDescription('Clear all metadata cache for a document manager.')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:cache:clear-metadata</info> command clears all metadata cache for the default document manager:

  <info>./app/console doctrine:mongodb:cache:clear-metadata</info>

You can also optionally specify the <comment>--dm</comment> option to specify which document manager to clear the cache for:

  <info>./app/console doctrine:mongodb:cache:clear-metadata --dm=default</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        return parent::execute($input, $output);
    }
}
