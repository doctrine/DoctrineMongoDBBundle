<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\CacheWarmer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use function dirname;
use function file_exists;
use function is_writable;
use function mkdir;
use function sprintf;

/**
 * The hydrator generator cache warmer generates all document hydrators.
 *
 * In the process of generating hydrators the cache for all the metadata is primed also,
 * since this information is necessary to build the hydrators in the first place.
 */
class HydratorCacheWarmer implements CacheWarmerInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * This cache warmer is not optional, without hydrators fatal error occurs!
     *
     * @return false
     */
    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir)
    {
        // register custom types during cache warm up to avoid errors while generating hydrator classes
        $this->registerTypes();

        // we need the directory no matter the hydrator cache generation strategy.
        $hydratorCacheDir = $this->container->getParameter('doctrine_mongodb.odm.hydrator_dir');
        if (! file_exists($hydratorCacheDir)) {
            if (@mkdir($hydratorCacheDir, 0775, true) === false) {
                throw new RuntimeException(sprintf('Unable to create the Doctrine Hydrator directory (%s)', dirname($hydratorCacheDir)));
            }
        } elseif (! is_writable($hydratorCacheDir)) {
            throw new RuntimeException(sprintf('Doctrine Hydrator directory (%s) is not writable for the current system user.', $hydratorCacheDir));
        }

        if ($this->container->getParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes') !== Configuration::AUTOGENERATE_NEVER) {
            return;
        }

        /** @var ManagerRegistry $registry */
        $registry = $this->container->get('doctrine_mongodb');
        foreach ($registry->getManagers() as $dm) {
            /** @var DocumentManager $dm */
            $classes = $dm->getMetadataFactory()->getAllMetadata();
            $dm->getHydratorFactory()->generateHydratorClasses($classes);
        }
    }

    private function registerTypes() : void
    {
        if (! $this->container->hasParameter('doctrine_mongodb.odm.types')) {
            // no types defined
            return;
        }

        $types = $this->container->getParameter('doctrine_mongodb.odm.types');
        foreach ($types as $key => $fqcn) {
            if (Type::hasType($key)) {
                Type::overrideType($key, $fqcn);
            } else {
                Type::addType($key, $fqcn);
            }
        }
    }
}
