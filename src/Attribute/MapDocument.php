<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Attribute;

use Attribute;
use Doctrine\Bundle\MongoDBBundle\ArgumentResolver\DocumentValueResolver;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

use function is_string;
use function property_exists;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapDocument extends MapEntity
{
    public function __construct(
        public ?string $class = null,
        public ?string $objectManager = null,
        public ?string $expr = null,
        public ?array $mapping = null,
        public ?array $exclude = null,
        public ?bool $stripNull = null,
        public array|string|null $id = null,
        bool $disabled = false,
        string $resolver = DocumentValueResolver::class,
        public ?string $message = null,
    ) {
        if (! is_string($this->message)) {
            parent::__construct($class, $objectManager, $expr, $mapping, $exclude, $stripNull, $id, null, $disabled, $resolver);

            return;
        }

        if (! property_exists(MapEntity::class, 'message')) {
            throw new RuntimeException(
                'The "message" options is not supported at "MapDocument". Please upgrade "symfony/doctrine-bridge" to "^7.1".',
            );
        }

        parent::__construct($class, $objectManager, $expr, $mapping, $exclude, $stripNull, $id, null, $disabled, $resolver, $message);
    }
}
