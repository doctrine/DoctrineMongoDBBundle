<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\Cursor\TailableCursorProcessorInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Throwable;

use function sleep;
use function sprintf;

/**
 * Command helping to configure a daemon listening to a tailable cursor. Works only with capped collections.
 */
class TailCursorDoctrineODMCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var string */
    protected static $defaultName = 'doctrine:mongodb:tail-cursor';

    protected function configure()
    {
        $this
            ->setDescription('Tails a mongodb cursor and processes the documents that come through')
            ->addArgument('document', InputArgument::REQUIRED, 'The document we are going to tail the cursor for.')
            ->addArgument('finder', InputArgument::REQUIRED, 'The repository finder method which returns the cursor to tail.')
            ->addArgument('processor', InputArgument::REQUIRED, 'The service id to use to process the documents.')
            ->addOption('no-flush', null, InputOption::VALUE_NONE, 'If set, the document manager won\'t be flushed after each document processing')
            ->addOption('sleep-time', null, InputOption::VALUE_REQUIRED, 'The number of seconds to wait between two checks.', 10);
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm                   = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $repository           = $dm->getRepository($input->getArgument('document'));
        $repositoryReflection = new ReflectionClass($repository);
        $documentReflection   = $repository->getDocumentManager()->getMetadataFactory()->getMetadataFor($input->getArgument('document'))->getReflectionClass();
        $processor            = $this->getContainer()->get($input->getArgument('processor'));
        $sleepTime            = $input->getOption('sleep-time');

        if (! $processor instanceof TailableCursorProcessorInterface) {
            throw new InvalidArgumentException('A tailable cursor processor must implement the ProcessorInterface.');
        }

        $processorReflection = new ReflectionClass($processor);
        $method              = $input->getArgument('finder');

        $output->writeln(sprintf('Getting cursor for <info>%s</info> from <info>%s#%s</info>', $input->getArgument('document'), $repositoryReflection->getShortName(), $method));

        $cursor = $repository->$method();

        while (true) {
            while (! $cursor->hasNext()) {
                if (! $cursor->valid()) {
                    $output->writeln('<error>Invalid cursor, requerying</error>');
                    $cursor = $repository->$method();
                }

                $output->writeln('<comment>Nothing found, waiting to try again</comment>');
                // read all results so far, wait for more
                sleep($sleepTime);
            }

            $cursor->next();
            $document = $cursor->current();
            $id       = $document->getId();

            $output->writeln(sprintf('Processing <info>%s</info> with id of <info>%s</info>', $documentReflection->getShortName(), (string) $id));
            $output->writeln(sprintf('   <info>%s</info><comment>#</comment><info>process</info>(<info>%s</info> <comment>$document</comment>)', $processorReflection->getShortName(), $documentReflection->getShortName()));

            try {
                $processor->process($document);
            } catch (Throwable $e) {
                $output->writeln(sprintf('Error occurred while processing document: <error>%s</error>', $e->getMessage()));
                continue;
            }

            if (! $input->getOption('no-flush')) {
                $dm->flush();
            }

            $dm->clear();
        }

        return 0;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
