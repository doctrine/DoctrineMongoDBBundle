<?php

declare(strict_types=1);

use Symfony\Bridge\Doctrine\Serializer\Normalizer\CollectionDenormalizer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        // Shared with DoctrineBundle so the definition is registered only once when both bundles are installed
        ->set('doctrine.collection.denormalizer', CollectionDenormalizer::class)
            ->tag('serializer.normalizer');
};
