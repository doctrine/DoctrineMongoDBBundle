<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoaderInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurgeMode;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

use function assert;
use function class_exists;
use function implode;
use function sprintf;

/**
 * Load data fixtures from bundles.
 *
 * @internal
 */
final class LoadDataFixturesDoctrineODMCommand extends DoctrineODMCommand
{
    public function __construct(ManagerRegistry $registry, private SymfonyFixturesLoaderInterface $fixturesLoader)
    {
        parent::__construct($registry);
    }

    protected function configure(): void
    {
        $this
            ->setName('doctrine:mongodb:fixtures:load')
            ->setDescription('Load data fixtures to your database.')
            ->addOption('group', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Only load fixtures that belong to this group (use with --services)')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of flushing the database first.')
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->addOption('purge-with-delete', null, InputOption::VALUE_NONE, 'Purge the database using deleteMany() instead of dropping and recreating the collections.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:fixtures:load</info> command loads data fixtures from your application:

  <info>php %command.full_name%</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>php %command.full_name%</info> --append</info>

You can also choose to load only fixtures that live in a certain group:

    <info>php %command.full_name%</info> <comment>--group=group1</comment>

If the collection uses search indexes or encryption, you can use the <info>--purge-with-delete</info> option to keep the collections instead of dropping them when purging the database:

  <info>php %command.full_name%</info> --purge-with-delete
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dm = $this->getManagerRegistry()->getManager($input->getOption('dm'));
        assert($dm instanceof DocumentManager);

        $ui = new SymfonyStyle($input, $output);

        if ($input->isInteractive() && ! $input->getOption('append')) {
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion('Careful, database will be purged. Do you want to continue (y/N) ?', false);

            if (! $helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $groups   = $input->getOption('group');
        $fixtures = $this->fixturesLoader->getFixtures($groups);
        if (! $fixtures) {
            $message = 'Could not find any fixture services to load';

            if (! empty($groups)) {
                $message .= sprintf(' in the groups (%s)', implode(', ', $groups));
            }

            $ui->error($message . '.');

            return 1;
        }

        $purger = new MongoDBPurger($dm);
        if ($input->getOption('purge-with-delete')) {
            if (! class_exists(MongoDBPurgeMode::class)) {
                $ui->error('The --purge-with-delete option requires doctrine/data-fixtures >= 2.1.0.');

                return self::INVALID;
            }

            $purger->setPurgeMode(MongoDBPurgeMode::Delete);
        }

        $executor = new MongoDBExecutor($dm, $purger);
        $executor->setLogger(new class ($output) extends AbstractLogger {
            public function __construct(private OutputInterface $output)
            {
            }

            /**
             * @inheritdoc
             * @phpstan-ignore missingType.parameter
             */
            public function log($level, $message, array $context = []): void
            {
                $this->output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        });
        $executor->execute($fixtures, $input->getOption('append'));

        return 0;
    }
}
