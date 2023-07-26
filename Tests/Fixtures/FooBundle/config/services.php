<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('Doctrine\\Bundle\\MongoDBBundle\\Tests\\Fixtures\\FooBundle\\', '..')
        ->exclude('../{config,DataFixtures,Document}');
};
