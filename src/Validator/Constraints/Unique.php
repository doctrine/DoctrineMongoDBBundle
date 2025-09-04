<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Attribute;
use ReflectionProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

if ((new ReflectionProperty(UniqueEntity::class, 'service'))->hasType()) {
    /**
     * Constraint for the unique document validator
     *
     * @Annotation
     * @Target({"CLASS", "ANNOTATION"})
     */
    #[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
    class Unique extends UniqueEntity
    {
        public string $service = 'doctrine_odm.mongodb.unique';
    }
} else {
    // Compatibility for symfony/doctrine-bridge < 7.0
    /**
     * Constraint for the unique document validator
     *
     * @Annotation
     * @Target({"CLASS", "ANNOTATION"})
     */
    #[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
    class Unique extends UniqueEntity
    {
        /** @var string $service */
        public $service = 'doctrine_odm.mongodb.unique';
    }
}
