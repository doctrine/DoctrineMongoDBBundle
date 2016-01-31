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

namespace Doctrine\Bundle\MongoDBBundle\CacheWarmer;

use Doctrine\ODM\MongoDB\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * The hydrator generator cache warmer generates all document hydrators.
 *
 * In the process of generating hydrators the cache for all the metadata is primed also,
 * since this information is necessary to build the hydrators in the first place.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class HydratorCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
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
        // we need the directory no matter the hydrator cache generation strategy.
        $hydratorCacheDir = $this->container->getParameter('doctrine_mongodb.odm.hydrator_dir');
        if (!file_exists($hydratorCacheDir)) {
            if (false === @mkdir($hydratorCacheDir, 0775, true)) {
                throw new \RuntimeException(sprintf('Unable to create the Doctrine Hydrator directory (%s)', dirname($hydratorCacheDir)));
            }
        } else if (!is_writable($hydratorCacheDir)) {
            throw new \RuntimeException(sprintf('Doctrine Hydrator directory (%s) is not writable for the current system user.', $hydratorCacheDir));
        }

        if (Configuration::AUTOGENERATE_NEVER !== $this->container->getParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes')) {
            return;
        }

        /* @var $registry \Doctrine\Common\Persistence\ManagerRegistry */
        $registry = $this->container->get('doctrine_mongodb');
        foreach ($registry->getManagers() as $dm) {
            /* @var $dm \Doctrine\ODM\MongoDB\DocumentManager */
            $classes = $dm->getMetadataFactory()->getAllMetadata();
            $dm->getHydratorFactory()->generateHydratorClasses($classes);
        }
    }
}
