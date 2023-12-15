<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\HydratorCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\CacheWarmer\PersistentCollectionCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\CacheWarmer\ProxyCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\ManagerConfigurator;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use MongoDB\Client;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Bridge\Doctrine\Validator\DoctrineInitializer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function trigger_deprecation;

/** @internal */
final class DeprecateChangedClassParametersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach (
            [
                'doctrine_mongodb.odm.connection.class' => Client::class,
                'doctrine_mongodb.odm.configuration.class' => Configuration::class,
                'doctrine_mongodb.odm.document_manager.class' => DocumentManager::class,
                'doctrine_mongodb.odm.manager_configurator.class' => ManagerConfigurator::class,
                'doctrine_mongodb.odm.event_manager.class' => ContainerAwareEventManager::class,
                'doctrine_odm.mongodb.validator_initializer.class' => DoctrineInitializer::class,
                'doctrine_odm.mongodb.validator.unique.class' => UniqueEntityValidator::class,
                'doctrine_mongodb.odm.class' => ManagerRegistry::class,
                'doctrine_mongodb.odm.security.user.provider.class' => EntityUserProvider::class,
                'doctrine_mongodb.odm.proxy_cache_warmer.class' => ProxyCacheWarmer::class,
                'doctrine_mongodb.odm.hydrator_cache_warmer.class' => HydratorCacheWarmer::class,
                'doctrine_mongodb.odm.persistent_collection_cache_warmer.class' => PersistentCollectionCacheWarmer::class,
            ] as $parameter => $class
        ) {
            if (! $container->hasParameter($parameter) || $container->getParameter($parameter) === $class) {
                continue;
            }

            trigger_deprecation(
                'doctrine/mongodb-odm-bundle',
                '4.7',
                '"%s" parameter is deprecated, use a compiler pass to update the service instead.',
                $parameter,
            );
        }

        foreach (
            [
                'doctrine_mongodb.odm.cache.array.class' => 'Doctrine\Common\Cache\ArrayCache',
                'doctrine_mongodb.odm.cache.apc.class' => 'Doctrine\Common\Cache\ApcCache',
                'doctrine_mongodb.odm.cache.apcu.class' => 'Doctrine\Common\Cache\ApcuCache',
                'doctrine_mongodb.odm.cache.memcache.class' => 'Doctrine\Common\Cache\MemcacheCache',
                'doctrine_mongodb.odm.cache.memcache_host' => 'localhost',
                'doctrine_mongodb.odm.cache.memcache_port' => 11211,
                'doctrine_mongodb.odm.cache.memcache_instance.class' => 'Memcache',
                'doctrine_mongodb.odm.cache.xcache.class' => 'Doctrine\Common\Cache\XcacheCache',
                'doctrine_mongodb.odm.metadata.driver_chain.class' => MappingDriverChain::class,
                'doctrine_mongodb.odm.metadata.attribute.class' => AttributeDriver::class,
                'doctrine_mongodb.odm.metadata.xml.class' => XmlDriver::class,
            ] as $parameter => $class
        ) {
            if (! $container->hasParameter($parameter) || $container->getParameter($parameter) === $class) {
                continue;
            }

            trigger_deprecation(
                'doctrine/mongodb-odm-bundle',
                '4.7',
                '"%s" parameter is deprecated, this parameter is used internally for configuration.',
                $parameter,
            );
        }
    }
}
