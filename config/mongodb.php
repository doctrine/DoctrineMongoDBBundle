<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\MongoDBBundle\ManagerConfigurator;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ContainerRepositoryFactory;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
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

        ->set('doctrine_mongodb', ManagerRegistry::class)
            ->public()
            ->args([
                'MongoDB',
                '%doctrine_mongodb.odm.connections%',
                '%doctrine_mongodb.odm.document_managers%',
                '%doctrine_mongodb.odm.default_connection%',
                '%doctrine_mongodb.odm.default_document_manager%',
                abstract_arg('Proxy Interface Name'),
                service('service_container'),
            ])

        ->set('doctrine_mongodb.odm.listeners.resolve_target_document', ResolveTargetDocumentListener::class)

        ->set('doctrine_mongodb.odm.symfony.fixtures.loader', SymfonyFixturesLoader::class);
};
