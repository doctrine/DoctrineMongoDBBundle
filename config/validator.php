<?php

declare(strict_types=1);

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Bridge\Doctrine\Validator\DoctrineInitializer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('doctrine_odm.mongodb.validator.unique', UniqueEntityValidator::class)
            ->tag('validator.constraint_validator', ['alias' => 'doctrine_odm.mongodb.unique'])
            ->args([
                service('doctrine_mongodb'),
            ])

        ->set('doctrine_odm.mongodb.validator_initializer', DoctrineInitializer::class)
            ->tag('validator.initializer')
            ->args([
                service('doctrine_mongodb'),
            ]);
};
