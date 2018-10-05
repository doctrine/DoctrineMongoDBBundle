<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\DocumentRepositoryGenerator;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;
use function strpos;

/**
 * Command to generate repository classes for mapping information.
 */
class GenerateRepositoriesDoctrineODMCommand extends DoctrineODMCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:mongodb:generate:repositories')
            ->setDescription('Generate repository classes from your mapping information.')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to initialize the repositories in.')
            ->addOption('document', null, InputOption::VALUE_OPTIONAL, 'The document class to generate the repository for (shortname without namespace).')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:generate:repositories</info> command generates the configured document repository classes from your mapping information:

  <info>./app/console doctrine:mongodb:generate:repositories</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleName     = $input->getArgument('bundle');
        $filterDocument = $input->getOption('document');

        $foundBundle = $this->findBundle($bundleName);
        $metadatas   = $this->getBundleMetadatas($foundBundle);

        if (! $metadatas) {
            throw new RuntimeException('Bundle ' . $bundleName . ' does not contain any mapped documents.');
        }

        $output->writeln(sprintf('Generating document repositories for "<info>%s</info>"', $foundBundle->getName()));
        $generator = new DocumentRepositoryGenerator();

        foreach ($metadatas as $metadata) {
            if ($filterDocument && $filterDocument !== $metadata->reflClass->getShortname()) {
                continue;
            }

            if ($metadata->customRepositoryClassName) {
                if (strpos($metadata->customRepositoryClassName, $foundBundle->getNamespace()) === false) {
                    throw new RuntimeException(
                        'Repository ' . $metadata->customRepositoryClassName . " and bundle don't have a common namespace, " .
                        'generation failed because the target directory cannot be detected.'
                    );
                }

                $output->writeln(sprintf('  > <info>OK</info> generating <comment>%s</comment>', $metadata->customRepositoryClassName));
                $generator->writeDocumentRepositoryClass(
                    $metadata->customRepositoryClassName,
                    $foundBundle->getPath(),
                    $foundBundle->getNamespace()
                );
            } else {
                $output->writeln(sprintf('  > <error>SKIP</error> no custom repository for <comment>%s</comment>', $metadata->name));
            }
        }
    }
}
