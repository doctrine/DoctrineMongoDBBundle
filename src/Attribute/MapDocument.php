<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Attribute;

use Attribute;
use Closure;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use InvalidArgumentException;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\Request;

use function is_string;
use function property_exists;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapDocument extends MapEntity
{
    /**
     * @param string|Closure(Request|InputInterface, DocumentRepository<object>):(object|iterable<object>|null)|null $expr
     *        An ExpressionLanguage expression or a closure to fetch the document.
     */
    public function __construct(
        ?string $class = null,
        ?string $objectManager = null,
        string|Closure|null $expr = null,
        ?array $mapping = null,
        ?array $exclude = null,
        ?bool $stripNull = null,
        array|string|null $id = null,
        bool $disabled = false,
        string $resolver = 'doctrine_mongodb.odm.document_value_resolver',
        ?string $message = null,
    ) {
        if ($expr instanceof Closure && ! self::exprAcceptsClosure()) {
            throw new InvalidArgumentException(
                'Using a closure in the "expr" option of "#[MapDocument]" requires symfony/doctrine-bridge 8.2 or higher.',
            );
        }

        if (! is_string($message)) {
            /** @phpstan-ignore argument.type (MapEntity accepts closures since symfony/doctrine-bridge 8.2) */
            parent::__construct($class, $objectManager, $expr, $mapping, $exclude, $stripNull, $id, null, $disabled, $resolver);

            return;
        }

        if (! property_exists(MapEntity::class, 'message')) {
            throw new InvalidArgumentException(
                'Using the "message" option of "#[MapDocument]" requires symfony/doctrine-bridge 7.1 or higher.',
            );
        }

        /** @phpstan-ignore argument.type (MapEntity accepts closures since symfony/doctrine-bridge 8.2) */
        parent::__construct($class, $objectManager, $expr, $mapping, $exclude, $stripNull, $id, null, $disabled, $resolver, $message);
    }

    /**
     * symfony/doctrine-bridge 8.2 widened MapEntity::$expr from "?string" to "string|Closure|null".
     */
    private static function exprAcceptsClosure(): bool
    {
        return (new ReflectionProperty(MapEntity::class, 'expr'))->getType() instanceof ReflectionUnionType;
    }
}
