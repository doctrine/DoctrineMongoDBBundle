<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\Command\ClearMetadataCacheDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\CreateSchemaDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\DropSchemaDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\GenerateHydratorsDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\GenerateProxiesDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\InfoDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\LoadDataFixturesDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\QueryDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\ShardDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Command\UpdateSchemaDoctrineODMCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set('doctrine_mongodb.odm.command.clear_metadata_cache', ClearMetadataCacheDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:cache:clear-metadata'])

        ->set('doctrine_mongodb.odm.command.create_schema', CreateSchemaDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:schema:create'])

        ->set('doctrine_mongodb.odm.command.drop_schema', DropSchemaDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:schema:drop'])

        ->set('doctrine_mongodb.odm.command.generate_hydrators', GenerateHydratorsDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:generate:hydrators'])

        ->set('doctrine_mongodb.odm.command.generate_proxies', GenerateProxiesDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:generate:proxies'])

        ->set('doctrine_mongodb.odm.command.info', InfoDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:mapping:info'])
            ->args([
                service('doctrine_mongodb'),
            ])

        ->set('doctrine_mongodb.odm.command.load_data_fixtures', LoadDataFixturesDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:fixtures:load'])
            ->args([
                service('doctrine_mongodb'),
                service('doctrine_mongodb.odm.symfony.fixtures.loader'),
            ])

        ->set('doctrine_mongodb.odm.command.query', QueryDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:query'])

        ->set('doctrine_mongodb.odm.command.shard', ShardDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:schema:shard'])

        ->set('doctrine_mongodb.odm.command.update_schema', UpdateSchemaDoctrineODMCommand::class)
            ->tag('console.command', ['command' => 'doctrine:mongodb:schema:update']);
};
