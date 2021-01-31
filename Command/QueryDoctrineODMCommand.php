<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute a Doctrine MongoDB ODM query and output the results.
 */
class QueryDoctrineODMCommand extends QueryCommand
{
    /** @var string */
    protected static $defaultName = 'doctrine:mongodb:query';

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.');
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
