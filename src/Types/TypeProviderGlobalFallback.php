<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Types;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeProvider;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Generator;
use IteratorAggregate;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_keys;

/**
 * Provides the types registered as services, and falls back to the global type registry for
 * every other name.
 *
 * The scoped registry must not be queried first for the names it does not declare explicitly:
 * it also knows the built-in types, and would hide the built-in types overridden in the global
 * registry with Type::overrideType(). Only the services of the document manager take precedence.
 *
 * A document manager that has its own type provider does not read the global type registry
 * anymore, so the fallback keeps those types available, for example a type registered by
 * another bundle or by the application itself.
 *
 * @internal
 *
 * @implements IteratorAggregate<string, Type>
 */
final class TypeProviderGlobalFallback implements TypeProvider, IteratorAggregate
{
    private static ?TypeProvider $globalProvider = null;

    /** @param ServiceProviderInterface<Type> $services */
    public function __construct(
        private readonly TypeRegistry $registry,
        private readonly ServiceProviderInterface $services,
    ) {
    }

    public function get(string $name): Type
    {
        if ($this->services->has($name)) {
            return $this->registry->get($name);
        }

        return $this->globalProvider()->get($name);
    }

    public function has(string $name): bool
    {
        return $this->services->has($name) || $this->globalProvider()->has($name);
    }

    /** @return Generator<string, Type> */
    public function getIterator(): Generator
    {
        $names = [];

        foreach (array_keys($this->services->getProvidedServices()) as $name) {
            $names[$name] = true;

            yield $name => $this->registry->get($name);
        }

        foreach ($this->globalProvider() as $name => $type) {
            if (isset($names[$name])) {
                continue;
            }

            yield $name => $type;
        }
    }

    /**
     * Returns the provider of the global type registry, which Type::registerType() writes to.
     */
    private function globalProvider(): TypeProvider
    {
        return self::$globalProvider ??= (new Configuration())->getTypeProvider();
    }
}
