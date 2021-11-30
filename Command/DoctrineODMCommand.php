<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function assert;
use function sprintf;
use function str_replace;
use function strtolower;
use function trigger_deprecation;

use const DIRECTORY_SEPARATOR;

/**
 * Base class for Doctrine ODM console commands to extend.
 *
 * @internal since version 5.0
 */
abstract class DoctrineODMCommand extends Command
{
    /** @var ContainerInterface|null */
    protected $container;

    /** @var ManagerRegistry|null */
    private $managerRegistry;

    public function __construct(?ManagerRegistry $registry = null)
    {
        parent::__construct();

        $this->managerRegistry = $registry;
    }

    /**
     * @deprecated since version 4.4
     */
    public function setContainer(?ContainerInterface $container = null)
    {
        trigger_deprecation(
            'doctrine/mongodb-odm-bundle',
            '4.4',
            'The "%s" method is deprecated and will be dropped in DoctrineMongoDBBundle 5.0.',
            __METHOD__
        );

        $this->container = $container;
    }

    /**
     * @deprecated since version 4.4
     *
     * @return ContainerInterface
     *
     * @throws LogicException
     */
    protected function getContainer()
    {
        trigger_deprecation(
            'doctrine/mongodb-odm-bundle',
            '4.4',
            'The "%s" method is deprecated and will be dropped in DoctrineMongoDBBundle 5.0.',
            __METHOD__
        );

        if ($this->container === null) {
            $application = $this->getApplication();
            if ($application === null) {
                throw new LogicException('The container cannot be retrieved as the application instance is not yet set.');
            }

            assert($application instanceof Application);

            $this->container = $application->getKernel()->getContainer();
        }

        return $this->container;
    }

    /**
     * @param string $dmName
     */
    public static function setApplicationDocumentManager(Application $application, $dmName)
    {
        $dm        = $application->getKernel()->getContainer()->get('doctrine_mongodb')->getManager($dmName);
        $helperSet = $application->getHelperSet();
        $helperSet->set(new DocumentManagerHelper($dm), 'dm');
    }

    /**
     * @deprecated since version 4.4
     *
     * @return ObjectManager[]
     */
    protected function getDoctrineDocumentManagers()
    {
        trigger_deprecation(
            'doctrine/mongodb-odm-bundle',
            '4.4',
            'The "%s" method is deprecated and will be dropped in DoctrineMongoDBBundle 5.0.',
            __METHOD__
        );

        return $this->getManagerRegistry()->getManagers();
    }

    /**
     * @internal
     *
     * @return ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        if ($this->managerRegistry === null) {
            $this->managerRegistry = $this->container->get('doctrine_mongodb');
            assert($this->managerRegistry instanceof ManagerRegistry);
        }

        return $this->managerRegistry;
    }

    /**
     * @deprecated since version 4.4
     *
     * @param string $bundleName
     *
     * @return Bundle
     */
    protected function findBundle($bundleName)
    {
        trigger_deprecation(
            'doctrine/mongodb-odm-bundle',
            '4.4',
            'The "%s" method is deprecated and will be dropped in DoctrineMongoDBBundle 5.0.',
            __METHOD__
        );

        $foundBundle = false;

        $application = $this->getApplication();

        assert($application instanceof Application);

        foreach ($application->getKernel()->getBundles() as $bundle) {
            assert($bundle instanceof Bundle);
            if (strtolower($bundleName) === strtolower($bundle->getName())) {
                $foundBundle = $bundle;

                break;
            }
        }

        if (! $foundBundle) {
            throw new InvalidArgumentException('No bundle ' . $bundleName . ' was found.');
        }

        return $foundBundle;
    }

    /**
     * Transform classname to a path $foundBundle substract it to get the destination
     *
     * @deprecated since version 4.4
     *
     * @param Bundle $bundle
     *
     * @return string
     */
    protected function findBasePathForBundle($bundle)
    {
        trigger_deprecation(
            'doctrine/mongodb-odm-bundle',
            '4.4',
            'The "%s" method is deprecated and will be dropped in DoctrineMongoDBBundle 5.0.',
            __METHOD__
        );

        $path        = str_replace('\\', DIRECTORY_SEPARATOR, $bundle->getNamespace());
        $search      = str_replace('\\', DIRECTORY_SEPARATOR, $bundle->getPath());
        $destination = str_replace(DIRECTORY_SEPARATOR . $path, '', $search, $c);

        if ($c !== 1) {
            throw new RuntimeException(sprintf('Can\'t find base path for bundle (path: "%s", destination: "%s").', $path, $destination));
        }

        return $destination;
    }
}
