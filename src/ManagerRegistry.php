<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectManager;
use ProxyManager\Proxy\LazyLoadingInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Bridge\Doctrine\ManagerRegistry as BaseManagerRegistry;
use Symfony\Component\VarExporter\LazyObjectInterface;
use Symfony\Contracts\Service\ResetInterface;

use function assert;

use const PHP_VERSION_ID;

class ManagerRegistry extends BaseManagerRegistry implements ResetInterface
{
    /**
     * @param string[]                $connections
     * @param string[]                $managers
     * @param class-string            $proxyInterfaceName The interface implemented by the proxies of the document managers
     * @param ContainerInterface|null $metadataDrivers    The metadata drivers of the document managers, by manager name, used to find the manager of a class without initializing lazy managers
     */
    public function __construct(
        string $name,
        array $connections,
        array $managers,
        string $defaultConnection,
        string $defaultManager,
        private readonly string $proxyInterfaceName,
        ?ContainerInterface $container = null,
        private readonly ?ContainerInterface $metadataDrivers = null,
    ) {
        $this->container = $container;

        parent::__construct($name, $connections, $managers, $defaultConnection, $defaultManager, $proxyInterfaceName);
    }

    public function getManagerForClass(string $class): ?ObjectManager
    {
        if ($this->metadataDrivers === null) {
            return parent::getManagerForClass($class);
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAnonymous() || $reflection->implementsInterface($this->proxyInterfaceName)) {
            return parent::getManagerForClass($class);
        }

        foreach ($this->getManagerNames() as $name => $serviceId) {
            if ($this->isTransient($class, $name, $serviceId)) {
                continue;
            }

            $manager = $this->getService($serviceId);
            assert($manager instanceof ObjectManager);

            return $manager;
        }

        return null;
    }

    /**
     * Clears all document managers.
     */
    public function reset(): void
    {
        foreach ($this->getManagerNames() as $managerName => $serviceId) {
            $this->resetOrClearManager($managerName, $serviceId);
        }
    }

    private function resetOrClearManager(string $managerName, string $serviceId): void
    {
        if (! $this->container->initialized($serviceId)) {
            return;
        }

        $manager = $this->container->get($serviceId);

        if ($manager instanceof LazyLoadingInterface || $manager instanceof LazyObjectInterface) {
            $this->resetManager($managerName);

            return;
        }

        assert($manager instanceof DocumentManager);

        if (! $manager->isOpen()) {
            return;
        }

        $manager->clear();
    }

    /** @param class-string $class */
    private function isTransient(string $class, string $name, string $serviceId): bool
    {
        if ($this->metadataDrivers?->has($name) && $this->isUninitialized($serviceId)) {
            $driver = $this->metadataDrivers->get($name);
            assert($driver instanceof MappingDriver);

            return $driver->isTransient($class);
        }

        $manager = $this->getService($serviceId);
        assert($manager instanceof ObjectManager);

        return $manager->getMetadataFactory()->isTransient($class);
    }

    private function isUninitialized(string $serviceId): bool
    {
        if (! $this->container->initialized($serviceId)) {
            return true;
        }

        $manager = $this->container->get($serviceId);

        if ($manager instanceof LazyObjectInterface) {
            return ! $manager->isLazyObjectInitialized();
        }

        // Native lazy objects are only supported on PHP 8.4 and later.
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        return (new ReflectionClass($manager))->isUninitializedLazyObject($manager);
    }
}
