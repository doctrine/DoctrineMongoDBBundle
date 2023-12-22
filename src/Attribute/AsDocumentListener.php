<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Attribute;

use Attribute;

/**
 * Service tag to autoconfigure document listeners.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AsDocumentListener
{
    public function __construct(
        public ?string $event = null,
        public ?string $connection = null,
        public ?int $priority = null,
    ) {
    }
}
