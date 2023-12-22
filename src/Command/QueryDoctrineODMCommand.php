<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute a Doctrine MongoDB ODM query and output the results.
 *
 * @internal
 */
final class QueryDoctrineODMCommand extends QueryCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:query')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        return parent::execute($input, $output);
    }
}
