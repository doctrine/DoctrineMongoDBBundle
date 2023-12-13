<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\APM\CommandLoggerRegistry;
use Doctrine\Bundle\MongoDBBundle\APM\PSRCommandLogger;
use Doctrine\Bundle\MongoDBBundle\APM\StopwatchCommandLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('doctrine_mongodb.odm.command_logger_registry', CommandLoggerRegistry::class)
            ->public()
            ->args([
                tagged_iterator('doctrine_mongodb.odm.command_logger'),
            ])

        ->set('doctrine_mongodb.odm.stopwatch_command_logger', StopwatchCommandLogger::class)
            ->args([
                service('debug.stopwatch')->nullOnInvalid(),
            ])

        ->set('doctrine_mongodb.odm.psr_command_logger', PSRCommandLogger::class)
            ->tag('monolog.logger', ['channel' => 'doctrine'])
            ->args([
                service('logger')->nullOnInvalid(),
            ]);
};
