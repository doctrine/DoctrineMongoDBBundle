<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Load data fixtures from bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class LoadDataFixturesDoctrineODMCommand extends DoctrineODMCommand
{
    /**
     * @return boolean
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
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager($input->getOption('dm'));

        $dirOrFile = $input->getOption('fixtures');
        $bundles = $input->getOption('bundles');
        if ($bundles && $dirOrFile) {
            throw new \InvalidArgumentException('Use only one option: --bundles or --fixtures.');
        }

        if ($input->isInteractive() && !$input->getOption('append')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Careful, database will be purged. Do you want to continue (y/N) ?', false);

            if (! $helper->ask($input, $output, $question)) {
                return;
            }
        }

        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : [$dirOrFile];
        } elseif ($bundles) {
            $kernel = $this->getContainer()->get('kernel');
            $paths = [$kernel->getRootDir().'/DataFixtures/MongoDB'];
            foreach ($bundles as $bundle) {
                $paths[] = $kernel->getBundle($bundle)->getPath();
            }
        } else {
            $kernel = $this->getContainer()->get('kernel');
            $paths = $this->getContainer()->getParameter('doctrine_mongodb.odm.fixtures_dirs');
            $paths = is_array($paths) ? $paths : [$paths];
            $paths[] = $kernel->getRootDir().'/DataFixtures/MongoDB';
            foreach ($kernel->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/MongoDB';
            }
        }

        $loaderClass = $this->getContainer()->getParameter('doctrine_mongodb.odm.fixture_loader');
        $loader = new $loaderClass($this->getContainer());
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            } else if (is_file($path)) {
                $loader->loadFromFile($path);
            }
        }

        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $purger = new MongoDBPurger($dm);
        $executor = new MongoDBExecutor($dm, $purger);
        $executor->setLogger(function($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));
    }
}
