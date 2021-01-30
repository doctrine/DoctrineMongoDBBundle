<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function assert;
use function sprintf;
use function str_replace;
use function strtolower;

use const DIRECTORY_SEPARATOR;

/**
 * Base class for Doctrine ODM console commands to extend.
 */
abstract class DoctrineODMCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var ManagerRegistry|null */
    private $managerRegistry;

    public function __construct(?ManagerRegistry $registry = null)
    {
        parent::__construct(null);

        $this->managerRegistry = $registry;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
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
     * @return ObjectManager[]
     */
    protected function getDoctrineDocumentManagers()
    {
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
        }

        return $this->managerRegistry;
    }

    /**
     * @param string $bundleName
     *
     * @return Bundle
     */
    protected function findBundle($bundleName)
    {
        $foundBundle = false;
        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
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
     * @param Bundle $bundle
     *
     * @return string
     */
    protected function findBasePathForBundle($bundle)
    {
        $path        = str_replace('\\', DIRECTORY_SEPARATOR, $bundle->getNamespace());
        $search      = str_replace('\\', DIRECTORY_SEPARATOR, $bundle->getPath());
        $destination = str_replace(DIRECTORY_SEPARATOR . $path, '', $search, $c);

        if ($c !== 1) {
            throw new RuntimeException(sprintf('Can\'t find base path for bundle (path: "%s", destination: "%s").', $path, $destination));
        }

        return $destination;
    }
}
