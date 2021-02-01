<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\CacheWarmer;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use function array_filter;
use function assert;
use function dirname;
use function file_exists;
use function is_writable;
use function mkdir;
use function sprintf;

/**
 * The proxy generator cache warmer generates all document proxies.
 *
 * In the process of generating proxies the cache for all the metadata is primed also,
 * since this information is necessary to build the proxies in the first place.
 */
class ProxyCacheWarmer implements CacheWarmerInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * This cache warmer is not optional, without proxies fatal error occurs!
     *
     * @return false
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        // we need the directory no matter the proxy cache generation strategy.
        $proxyCacheDir = $this->container->getParameter('doctrine_mongodb.odm.proxy_dir');
        if (! file_exists($proxyCacheDir)) {
            if (@mkdir($proxyCacheDir, 0775, true) === false) {
                throw new RuntimeException(sprintf('Unable to create the Doctrine Proxy directory (%s)', dirname($proxyCacheDir)));
            }
        } elseif (! is_writable($proxyCacheDir)) {
            throw new RuntimeException(sprintf('Doctrine Proxy directory (%s) is not writable for the current system user.', $proxyCacheDir));
        }

        if ($this->container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes') === Configuration::AUTOGENERATE_EVAL) {
            return;
        }

        $registry = $this->container->get('doctrine_mongodb');
        assert($registry instanceof ManagerRegistry);
        foreach ($registry->getManagers() as $dm) {
            /** @var DocumentManager $dm */
            $classes = $this->getClassesForProxyGeneration($dm);
            $dm->getProxyFactory()->generateProxyClasses($classes);
        }
    }

    /**
     * @return ClassMetadata[]
     */
    private function getClassesForProxyGeneration(DocumentManager $dm)
    {
        return array_filter($dm->getMetadataFactory()->getAllMetadata(), static function (ClassMetadata $metadata) {
            return ! $metadata->isEmbeddedDocument && ! $metadata->isMappedSuperclass;
        });
    }
}
