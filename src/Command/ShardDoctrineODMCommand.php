<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\ShardCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to shard database collections for a set of classes based on their
 * mappings.
 *
 * @internal
 */
final class ShardDoctrineODMCommand extends ShardCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:schema:shard')
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:schema:shard</info> command shards collections based on their metadata:

  <info>./app/console doctrine:mongodb:schema:shard</info>

You can also optionally specify the name of a document manager to shard collections for:

  <info>./app/console doctrine:mongodb:schema:shard --dm=default</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        return parent::execute($input, $output);
    }
}
