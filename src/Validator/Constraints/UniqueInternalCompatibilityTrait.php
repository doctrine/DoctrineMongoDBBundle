<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use ReflectionProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

if ((new ReflectionProperty(UniqueEntity::class, 'service'))->hasType()) {
    /**
     * Compatibility with Symfony versions >= 7.0
     *
     * @internal
     */
    trait UniqueInternalCompatibilityTrait
    {
        public string $service = 'doctrine_odm.mongodb.unique';
    }
} else {
    /**
     * Compatibility with Symfony versions < 7.0
     *
     * @internal
     */
    trait UniqueInternalCompatibilityTrait
    {
        /**
         * @var string $service
         * @phpstan-ignore missingType.property
         */
        public $service = 'doctrine_odm.mongodb.unique';
    }
}
