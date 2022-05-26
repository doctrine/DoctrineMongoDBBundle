<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to create the database schema for a set of classes based on their
 * mappings.
 */
class CreateSchemaDoctrineODMCommand extends CreateCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:schema:create')
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:schema:create</info> command creates the default document manager's schema:

  <info>./app/console doctrine:mongodb:schema:create</info>

You can also optionally specify the name of a document manager to create the schema for:

  <info>./app/console doctrine:mongodb:schema:create --dm=default</info>
EOT
        );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        return parent::execute($input, $output);
    }
}
