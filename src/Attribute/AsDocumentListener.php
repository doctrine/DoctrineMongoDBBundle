<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Attribute;

use Attribute;

use function trigger_deprecation;

/**
 * Service tag to autoconfigure document listeners.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AsDocumentListener
{
    public function __construct(
        public ?string $event = null,
        /** @deprecated the method name is the same as the event name */
        public ?string $method = null,
        /** @deprecated not supported */
        public ?bool $lazy = null,
        public ?string $connection = null,
        public ?int $priority = null,
    ) {
        // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ($method !== null) {
            trigger_deprecation(
                'doctrine/mongodb-odm-bundle',
                '4.7',
                'The method name is the same as the event name, so it can be omitted.',
            );
        }

        if ($lazy !== null) {
            trigger_deprecation(
                'doctrine/mongodb-odm-bundle',
                '4.7',
                'Lazy loading is not supported.',
            );
        }
    }
}
