<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\CacheWarmer;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use function assert;
use function dirname;
use function file_exists;
use function in_array;
use function is_dir;
use function is_writable;
use function mkdir;
use function sprintf;

/**
 * The persistent collections generator cache warmer generates all custom persistent collections.
 *
 * In the process of generating persistent collections the cache for all the metadata is primed also,
 * since this information is necessary to build the persistent collections in the first place.
 *
 * @internal since version 4.4
 *
 * @psalm-suppress ContainerDependency
 */
class PersistentCollectionCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * This cache warmer is not optional, without persistent collections fatal error occurs!
     *
     * @return false
     */
    public function isOptional()
    {
        return false;
    }

    /** @return string[] */
    public function warmUp(string $cacheDir)
    {
        // we need the directory no matter the hydrator cache generation strategy.
        $collCacheDir = (string) $this->container->getParameter('doctrine_mongodb.odm.persistent_collection_dir');
        if (! file_exists($collCacheDir)) {
            if (@mkdir($collCacheDir, 0775, true) === false && ! is_dir($collCacheDir)) {
                throw new RuntimeException(sprintf('Unable to create the Doctrine persistent collection directory (%s)', dirname($collCacheDir)));
            }
        } elseif (! is_writable($collCacheDir)) {
            throw new RuntimeException(sprintf('Doctrine persistent collection directory (%s) is not writable for the current system user.', $collCacheDir));
        }

        // if persistent collection are autogenerated we don't need to generate them in the cache warmer.
        if ($this->container->getParameter('doctrine_mongodb.odm.auto_generate_persistent_collection_classes') !== Configuration::AUTOGENERATE_NEVER) {
            return [];
        }

        $generated = [];
        $registry  = $this->container->get('doctrine_mongodb');
        assert($registry instanceof ManagerRegistry);
        foreach ($registry->getManagers() as $dm) {
            /** @var DocumentManager $dm */
            $collectionGenerator = $dm->getConfiguration()->getPersistentCollectionGenerator();
            $classes             = $dm->getMetadataFactory()->getAllMetadata();
            foreach ($classes as $metadata) {
                foreach ($metadata->getAssociationNames() as $fieldName) {
                    $mapping = $metadata->getFieldMapping($fieldName);
                    if (empty($mapping['collectionClass']) || in_array($mapping['collectionClass'], $generated)) {
                        continue;
                    }

                    $generated[] = $mapping['collectionClass'];
                    $collectionGenerator->generateClass($mapping['collectionClass'], $collCacheDir);
                }
            }
        }

        return [];
    }
}
