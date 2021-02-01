<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update the database schema for a set of classes based on their
 * mappings.
 */
class UpdateSchemaDoctrineODMCommand extends UpdateCommand
{
    /** @var string */
    protected static $defaultName = 'doctrine:mongodb:schema:update';

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:schema:update</info> command updates the default document manager's schema:

  <info>./app/console doctrine:mongodb:schema:update</info>

You can also optionally specify the name of a document manager to update the schema for:

  <info>./app/console doctrine:mongodb:schema:update --dm=default</info>
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
