<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Attribute;

use Attribute;
use Doctrine\Bundle\MongoDBBundle\ArgumentResolver\DocumentValueResolver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

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
    ) {
        parent::__construct($class, $objectManager, $expr, $mapping, $exclude, $stripNull, $id, null, $disabled, $resolver);
    }
}
