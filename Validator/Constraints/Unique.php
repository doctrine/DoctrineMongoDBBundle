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
    /**
     * @param array|string      $fields     The combination of fields that must contain unique values or a set of options
     * @param bool|array|string $ignoreNull The combination of fields that ignore null values
     */
    public function __construct(
        $fields,
        string $message = null,
        string $service = 'doctrine_odm.mongodb.unique',
        string $em = null,
        string $entityClass = null,
        string $repositoryMethod = null,
        string $errorPath = null,
        bool|string|array $ignoreNull = null,
        array $groups = null,
        $payload = null,
        array $options = []
    ) {
        parent::__construct(
            $fields,
            $message,
            $service,
            $em,
            $entityClass,
            $repositoryMethod,
            $errorPath,
            $ignoreNull,
            $groups,
            $payload,
            $options
        );
    }
}
