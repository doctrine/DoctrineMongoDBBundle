<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate the Doctrine ORM document proxies to your cache directory.
 *
 * @internal
 */
final class GenerateProxiesDoctrineODMCommand extends GenerateProxiesCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:generate:proxies')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:generate:proxies</info> command generates proxy classes for your default document manager:

  <info>./app/console doctrine:mongodb:generate:proxies</info>

You can specify the document manager you want to generate the proxies for:

  <info>./app/console doctrine:mongodb:generate:proxies --dm=name</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        return parent::execute($input, $output);
    }
}
