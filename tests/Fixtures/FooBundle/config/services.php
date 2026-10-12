<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\ExpressionLanguage\CurrentIdExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('Doctrine\\Bundle\\MongoDBBundle\\Tests\\Fixtures\\FooBundle\\', '..')
        ->exclude('../{config,DataFixtures,Document}');

    $services->set(CurrentIdExpressionLanguageProvider::class)
        ->tag('doctrine_mongodb.odm.document_value_resolver.expression_language_provider');
};
