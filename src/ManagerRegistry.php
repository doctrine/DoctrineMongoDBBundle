<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;
use ProxyManager\Proxy\LazyLoadingInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry as BaseManagerRegistry;
use Symfony\Component\VarExporter\LazyObjectInterface;
use Symfony\Contracts\Service\ResetInterface;

use function assert;

class ManagerRegistry extends BaseManagerRegistry implements ResetInterface
{
    public function __construct(string $name, array $connections, array $managers, string $defaultConnection, string $defaultManager, string $proxyInterfaceName, ?ContainerInterface $container = null)
    {
        $this->container = $container;

        parent::__construct($name, $connections, $managers, $defaultConnection, $defaultManager, $proxyInterfaceName);
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
}
