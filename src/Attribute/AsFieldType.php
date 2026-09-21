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
     * @param string|null $type            The name of the field type, defaults to the class name
     * @param string|null $documentManager The name of the document manager this type is associated with
     */
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $documentManager = null,
    ) {
    }
}
