<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Attribute;

use Attribute;

/**
 * Marks a class as a Doctrine MongoDB ODM field type.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AsFieldType
{
    /**
     * @param string      $name          The name of the field type
     * @param string|null $objectManager The name of the document manager this type is associated with
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $objectManager = null,
    ) {
    }
}
