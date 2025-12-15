<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function sprintf;

/**
 * Field type registry that lazy-loads types from a service locator.
 */
class LazyTypeRegistry extends TypeRegistry
{
    /** @param ServiceLocator<Type> $locator */
    public function __construct(private ServiceProviderInterface $locator)
    {
    }

    public function has(string $name): bool
    {
        return $this->locator->has($name) || parent::has($name);
    }

    public function get(string $name): Type
    {
        if ($this->locator->has($name)) {
            return $this->locator->get($name);
        }

        return parent::get($name);
    }

    public function register(string $name, Type|string $type): void
    {
        if ($this->locator->has($name)) {
            throw new InvalidArgumentException(sprintf('Type "%s" is already registered in the service locator and cannot be overridden.', $name));
        }

        parent::register($name, $type);
    }

    /** @internal */
    public function getMap(): array
    {
        $map = parent::getMap();
        foreach ($this->locator->getProvidedServices() as $name => $type) {
            $map[$name] = $this->locator->get($name)::class;
        }

        return $map;
    }
}
