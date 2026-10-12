<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Attribute;
use Symfony\Bridge\Doctrine\Validator\Constraints\EntityExists;

use function class_exists;

if (class_exists(EntityExists::class)) {
    /**
     * Constraint for validating that a value references an existing document
     *
     * @Annotation
     * @Target({"PROPERTY", "METHOD"})
     */
    #[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class DocumentExists extends EntityExists
    {
        public function validatedBy(): string
        {
            return 'doctrine_odm.mongodb.entity_exists';
        }
    }
}
