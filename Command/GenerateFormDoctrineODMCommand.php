<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\DoctrineBundle\Mapping\MetadataFactory;
use Symfony\Bundle\DoctrineMongoDBBundle\Generator\DoctrineODMFormGenerator;
use Sensio\Bundle\GeneratorBundle\Command\Validators;

/**
 * Generates a form type class for a given Doctrine document.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class GenerateFormDoctrineODMCommand extends DoctrineODMCommand {

    /**
     * @see Command
     */
    protected function configure() {
        $this
                ->setDefinition(array(
                    new InputArgument('document', InputArgument::REQUIRED, 'The document class name to initialize (shortcut notation)'),
                ))
                ->setName('doctrine:mongodb:generate:form')
                ->setDescription('Generates a form types classes based on from your documents')
                ->setHelp(<<<EOT
The <info>doctrine:mongodb:generate:form</info> command generates form type classes based on Doctrine documents of a bundle.

<info>php app/console doctrine:mongodb:generate:form AcmeBlogBundle</info>
EOT
                )

        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $entity = Validators::validateEntityName($input->getArgument('document'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        $bundle = $this->findBundle($bundle);
        $entityClass = $bundle->getNamespace() . '\Document\\' . $entity;
        $metadata = $this->getDocumentMetadata($entityClass);

        $generator = new DoctrineODMFormGenerator($this->getContainer()->get('filesystem'), __DIR__ . '/../Resources/skeleton/form');
        $generator->generate($bundle, $metadata);
        $output->writeln('Generating the Form code: <info>OK</info>');
    }

}
