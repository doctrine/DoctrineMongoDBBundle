<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\MongoDBBundle\ManagerConfigurator;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;
use Doctrine\Bundle\MongoDBBundle\Repository\ContainerRepositoryFactory;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use MongoDB\Client;
use ProxyManager\Proxy\GhostObjectInterface;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('doctrine_mongodb.odm.cache.array.class', 'Doctrine\Common\Cache\ArrayCache')
        ->set('doctrine_mongodb.odm.cache.apc.class', 'Doctrine\Common\Cache\ApcCache')
        ->set('doctrine_mongodb.odm.cache.apcu.class', 'Doctrine\Common\Cache\ApcuCache')
        ->set('doctrine_mongodb.odm.cache.memcache.class', 'Doctrine\Common\Cache\MemcacheCache')
        ->set('doctrine_mongodb.odm.cache.memcache_host', 'localhost')
        ->set('doctrine_mongodb.odm.cache.memcache_port', 11211)
        ->set('doctrine_mongodb.odm.cache.memcache_instance.class', 'Memcache')
        ->set('doctrine_mongodb.odm.cache.xcache.class', 'Doctrine\Common\Cache\XcacheCache')
        ->set('doctrine_mongodb.odm.connection.class', Client::class)
        ->set('doctrine_mongodb.odm.configuration.class', Configuration::class)
        ->set('doctrine_mongodb.odm.document_manager.class', DocumentManager::class)
        ->set('doctrine_mongodb.odm.manager_configurator.class', ManagerConfigurator::class)
        ->set('doctrine_mongodb.odm.class', ManagerRegistry::class)
        ->set('doctrine_mongodb.odm.metadata.driver_chain.class', MappingDriverChain::class)
        ->set('doctrine_mongodb.odm.metadata.attribute.class', AttributeDriver::class)
        ->set('doctrine_mongodb.odm.metadata.xml.class', XmlDriver::class)
        ->set('doctrine_mongodb.odm.mapping_dirs', [])
        ->set('doctrine_mongodb.odm.xml_mapping_dirs', '%doctrine_mongodb.odm.mapping_dirs%')
        ->set('doctrine_mongodb.odm.document_dirs', [])
        ->set('doctrine_mongodb.odm.fixtures_dirs', []);

    $containerConfigurator->services()

        ->alias(DocumentManager::class, 'doctrine_mongodb.odm.document_manager')

        ->alias(ManagerRegistry::class, 'doctrine_mongodb')

        ->set('doctrine_mongodb.odm.connection.event_manager', ContainerAwareEventManager::class)
            ->abstract()
            ->args([
                service('service_container'),
            ])

        ->set('doctrine_mongodb.odm.container_repository_factory', ContainerRepositoryFactory::class)
            ->args([
                abstract_arg('service locator'),
            ])

        ->set('doctrine_mongodb.odm.manager_configurator.abstract', ManagerConfigurator::class)
            ->abstract()
            ->args([
                abstract_arg('enabled filters'),
            ])

        ->set('doctrine_mongodb.odm.security.user.provider', EntityUserProvider::class)
            ->abstract()
            ->args([
                service('doctrine_mongodb'),
            ])

        ->set('doctrine_mongodb', '%doctrine_mongodb.odm.class%')
            ->public()
            ->args([
                'MongoDB',
                '%doctrine_mongodb.odm.connections%',
                '%doctrine_mongodb.odm.document_managers%',
                '%doctrine_mongodb.odm.default_connection%',
                '%doctrine_mongodb.odm.default_document_manager%',
                GhostObjectInterface::class,
                service('service_container'),
            ])

        ->set('doctrine_mongodb.odm.listeners.resolve_target_document', ResolveTargetDocumentListener::class)

        ->set('doctrine_mongodb.odm.symfony.fixtures.loader', SymfonyFixturesLoader::class);
};
