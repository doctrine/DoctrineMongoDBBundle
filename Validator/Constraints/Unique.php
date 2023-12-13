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
     * @param string[]|string      $fields     The combination of fields that must contain unique values or a set of options
     * @param bool|string[]|string $ignoreNull The combination of fields that ignore null values
     */
    public function __construct(
        array|string $fields,
        ?string $message = null,
        string $service = 'doctrine_odm.mongodb.unique',
        ?string $em = null,
        ?string $entityClass = null,
        ?string $repositoryMethod = null,
        ?string $errorPath = null,
        bool|array|string|null $ignoreNull = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
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
            $options,
        );
    }
}
