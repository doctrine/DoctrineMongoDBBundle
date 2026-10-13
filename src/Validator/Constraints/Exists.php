<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Attribute;
use Symfony\Bridge\Doctrine\Validator\Constraints\EntityExists;

/**
 * Constraint for validating that a value references an existing document
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Exists extends EntityExists
{
    public function validatedBy(): string
    {
        return 'doctrine_odm.mongodb.exists';
    }
}
