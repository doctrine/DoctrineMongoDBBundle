<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Attribute;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Constraint for the unique document validator
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Unique extends UniqueEntity
{
    /** @var string */
    public $service = 'doctrine_odm.mongodb.unique';
}
