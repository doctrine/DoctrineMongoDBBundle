<?php

declare(strict_types=1);

use Symfony\Bridge\Doctrine\Messenger\DoctrineClearEntityManagerWorkerSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('doctrine_mongodb.messenger.event_subscriber.doctrine_clear_document_manager', DoctrineClearEntityManagerWorkerSubscriber::class)
        ->tag('kernel.event_subscriber')
        ->args([
            service('doctrine_mongodb'),
        ]);
};
