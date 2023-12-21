<?php

declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBTypeGuesser;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('form.type.mongodb_document', DocumentType::class)
            ->tag('form.type', ['alias' => 'document'])
            ->args([
                service('doctrine_mongodb'),
            ])

        ->set('form.type_guesser.doctrine.mongodb', DoctrineMongoDBTypeGuesser::class)
            ->tag('form.type_guesser')
            ->args([
                service('doctrine_mongodb'),
            ]);
};
