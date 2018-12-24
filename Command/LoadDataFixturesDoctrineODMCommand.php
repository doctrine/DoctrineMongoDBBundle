<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\KernelInterface;
use function class_exists;
use function implode;
use function is_array;
use function is_dir;
use function is_file;
use function sprintf;

/**
 * Load data fixtures from bundles.
 */
class LoadDataFixturesDoctrineODMCommand extends DoctrineODMCommand
{
    /** @var KernelInterface|null */
    private $kernel;

    public function __construct(?ManagerRegistry $registry = null, ?KernelInterface $kernel = null)
    {
        parent::__construct($registry);

        $this->kernel = $kernel;
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
            ->setName('doctrine:mongodb:fixtures:load')
            ->setDescription('Load data fixtures to your database.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory or file to load data fixtures from.')
            ->addOption('bundles', 'b', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The bundles to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of flushing the database first.')
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:fixtures:load</info> command loads data fixtures from your bundles:

  <info>./app/console doctrine:mongodb:fixtures:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console doctrine:mongodb:fixtures:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console doctrine:mongodb:fixtures:load --append</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getManagerRegistry()->getManager($input->getOption('dm'));

        $dirOrFile = $input->getOption('fixtures');
        $bundles   = $input->getOption('bundles');
        if ($bundles && $dirOrFile) {
            throw new InvalidArgumentException('Use only one option: --bundles or --fixtures.');
        }

        if ($input->isInteractive() && ! $input->getOption('append')) {
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion('Careful, database will be purged. Do you want to continue (y/N) ?', false);

            if (! $helper->ask($input, $output, $question)) {
                return;
            }
        }

        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : [$dirOrFile];
        } elseif ($bundles) {
            $paths = [$this->getKernel()->getRootDir() . '/DataFixtures/MongoDB'];
            foreach ($bundles as $bundle) {
                $paths[] = $this->getKernel()->getBundle($bundle)->getPath();
            }
        } else {
            $paths   = $this->container->getParameter('doctrine_mongodb.odm.fixtures_dirs');
            $paths   = is_array($paths) ? $paths : [$paths];
            $paths[] = $this->getKernel()->getRootDir() . '/DataFixtures/MongoDB';
            foreach ($this->getKernel()->getBundles() as $bundle) {
                $paths[] = $bundle->getPath() . '/DataFixtures/MongoDB';
            }
        }

        $loaderClass = $this->container->getParameter('doctrine_mongodb.odm.fixture_loader');
        $loader      = new $loaderClass($this->container);
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            } elseif (is_file($path)) {
                $loader->loadFromFile($path);
            }
        }

        $fixtures = $loader->getFixtures();
        if (! $fixtures) {
            throw new InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- " . implode("\n- ", $paths))
            );
        }

        $purger   = new MongoDBPurger($dm);
        $executor = new MongoDBExecutor($dm, $purger);
        $executor->setLogger(static function ($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));
    }

    private function getKernel()
    {
        if ($this->kernel === null) {
            $this->kernel = $this->container->get('kernel');
        }

        return $this->kernel;
    }
}
