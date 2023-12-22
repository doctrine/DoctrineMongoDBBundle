<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\HydratorCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\CacheWarmer\PersistentCollectionCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\CacheWarmer\ProxyCacheWarmer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('doctrine_mongodb.odm.proxy_cache_warmer', ProxyCacheWarmer::class)
            ->tag('kernel.cache_warmer', ['priority' => 25])
            ->args([
                service('service_container'),
            ])

        ->set('doctrine_mongodb.odm.hydrator_cache_warmer', HydratorCacheWarmer::class)
            ->tag('kernel.cache_warmer', ['priority' => 25])
            ->args([
                service('service_container'),
            ])

        ->set('doctrine_mongodb.odm.persistent_collection_cache_warmer', PersistentCollectionCacheWarmer::class)
            ->tag('kernel.cache_warmer', ['priority' => 25])
            ->args([
                service('service_container'),
            ]);
};
