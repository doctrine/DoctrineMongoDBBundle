<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\DataCollector\CommandDataCollector;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set('doctrine_mongodb.odm.data_collector.command_logger', CommandLogger::class)

        ->set('doctrine_mongodb.odm.data_collector', CommandDataCollector::class)
            ->args([
                service('doctrine_mongodb.odm.data_collector.command_logger'),
            ]);
};
