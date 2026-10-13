<?php

declare(strict_types=1);

use Symfony\Bridge\Doctrine\ArgumentResolver\Console\EntityValueResolver as ConsoleEntityValueResolver;
use Symfony\Bridge\Doctrine\ArgumentResolver\EntityValueResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('doctrine_mongodb.odm.document_value_resolver', EntityValueResolver::class)
        ->tag('controller.argument_value_resolver', ['name' => 'doctrine_mongodb.odm.document_value_resolver', 'priority' => 110])
        ->args([
            service('doctrine_mongodb'),
            service('doctrine_mongodb.odm.document_value_resolver.expression_language')->ignoreOnInvalid(),
        ]);

    $services->set('doctrine_mongodb.odm.document_value_resolver.expression_language', ExpressionLanguage::class);

    $services->set('doctrine_mongodb.odm.document_value_resolver.console', ConsoleEntityValueResolver::class)
        ->tag('console.argument_value_resolver', ['name' => 'doctrine_mongodb.odm.document_value_resolver.console', 'priority' => 110])
        ->args([
            service('doctrine_mongodb'),
            service('doctrine_mongodb.odm.document_value_resolver.expression_language')->ignoreOnInvalid(),
        ]);
};
