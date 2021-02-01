<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoaderInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

use function class_exists;
use function implode;
use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Load data fixtures from bundles.
 */
class LoadDataFixturesDoctrineODMCommand extends DoctrineODMCommand
{
    /** @var string */
    protected static $defaultName = 'doctrine:mongodb:fixtures:load';

    /** @var SymfonyFixturesLoaderInterface  */
    private $fixturesLoader;

    public function __construct(?ManagerRegistry $registry = null, ?KernelInterface $kernel = null, ?SymfonyFixturesLoaderInterface $fixturesLoader = null)
    {
        parent::__construct($registry);

        $this->fixturesLoader = $fixturesLoader;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return parent::isEnabled() && class_exists(Loader::class);
    }

    protected function configure()
    {
        $this
            ->setDescription('Load data fixtures to your database.')
            ->addOption('services', null, InputOption::VALUE_NONE, 'Use services as fixtures')
            ->addOption('group', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Only load fixtures that belong to this group (use with --services)')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of flushing the database first.')
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:fixtures:load</info> command loads data fixtures from your application:

  <info>php %command.full_name%</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>php %command.full_name%</info> --append</info>


Alternatively, you can also load fixture services instead of files. Fixture services are tagged with `<comment>doctrine.fixture.odm.mongodb</comment>`.
Using `<comment>--services</comment>` will be the default behaviour in 5.0.
When loading fixture services, you can also choose to load only fixtures that live in a certain group:
`<info>php %command.full_name%</info> <comment>--group=group1</comment> <comment>--services</comment>`



EOT
        );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getManagerRegistry()->getManager($input->getOption('dm'));
        $ui = new SymfonyStyle($input, $output);

        if ((bool) $input->getOption('services')) {
            @trigger_error(sprintf('The "services" option to the "%s" command is deprecated and will be dropped in DoctrineMongoDBBundle 5.0.', $this->getName()), E_USER_DEPRECATED);
        }

        if ($input->isInteractive() && ! $input->getOption('append')) {
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion('Careful, database will be purged. Do you want to continue (y/N) ?', false);

            if (! $helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        if (! $this->fixturesLoader) {
            throw new RuntimeException('Cannot use fixture services without injecting a fixtures loader.');
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

        $purger   = new MongoDBPurger($dm);
        $executor = new MongoDBExecutor($dm, $purger);
        $executor->setLogger(static function ($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));

        return 0;
    }
}
