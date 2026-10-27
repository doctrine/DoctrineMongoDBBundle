<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Types;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeProvider;
use Generator;
use IteratorAggregate;

/**
 * Provides the types of the document manager registry, and falls back to the global type
 * registry for the types that other code registers with Type::registerType().
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

    public function __construct(private readonly TypeProvider $provider)
    {
    }

    public function get(string $name): Type
    {
        if ($this->provider->has($name)) {
            return $this->provider->get($name);
        }

        return $this->globalProvider()->get($name);
    }

    public function has(string $name): bool
    {
        return $this->provider->has($name) || $this->globalProvider()->has($name);
    }

    /** @return Generator<string, Type> */
    public function getIterator(): Generator
    {
        $names = [];

        foreach ($this->provider as $name => $type) {
            $names[$name] = true;

            yield $name => $type;
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
