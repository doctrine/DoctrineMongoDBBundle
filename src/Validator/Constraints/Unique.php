<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Attribute;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use function array_combine;
use function array_filter;
use function array_slice;
use function func_get_args;
use function func_num_args;

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
        ?string $service = null,
        ?string $em = null,
        ?string $entityClass = null,
        ?string $repositoryMethod = null,
        ?string $errorPath = null,
        bool|array|string|null $ignoreNull = null,
        ?array $identifierFieldNames = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        // Call the parent constructor using named arguments
        // symfony/doctrine-bridge 7.3 added the parameter $identifierFieldNames
        $args              = array_combine(
            array_slice(['fields', 'message', 'service', 'em', 'entityClass', 'repositoryMethod', 'errorPath', 'ignoreNull', 'identifierFieldNames', 'groups', 'payload', 'options'], 0, func_num_args()),
            func_get_args(),
        );
        $args              = array_filter($args, static fn ($v) => $v !== null);
        $args['service'] ??= 'doctrine_odm.mongodb.unique';

        parent::__construct(...$args);
    }
}
