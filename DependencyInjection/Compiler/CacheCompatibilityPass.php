<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function assert;
use function is_a;
use function is_string;
use function trigger_deprecation;

/** @internal */
final class CacheCompatibilityPass implements CompilerPassInterface
{
    private const CACHE_SETTER_METHODS_PSR6_SUPPORT = [
        'setMetadataCache' => true,
        'setMetadataCacheImpl' => true,
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(DoctrineMongoDBExtension::CONFIGURATION_TAG)) as $id) {
            /** @var array<int, string|Reference[]> $methodCall */
            foreach ($container->getDefinition($id)->getMethodCalls() as $methodCall) {
                $methodName = $methodCall[0];
                assert(is_string($methodName));

                if (! isset(self::CACHE_SETTER_METHODS_PSR6_SUPPORT[$methodName])) {
                    continue;
                }

                /** @var Reference[] $methodArgs */
                $methodArgs   = $methodCall[1];
                $definitionId = (string) $methodArgs[0];
                $aliasId      = null;
                if ($container->hasAlias($definitionId)) {
                    $aliasId      = $definitionId;
                    $definitionId = (string) $container->getAlias($aliasId);
                }

                $shouldBePsr6 = self::CACHE_SETTER_METHODS_PSR6_SUPPORT[$methodName];

                $this->wrapIfNecessary($container, $aliasId, $definitionId, $shouldBePsr6);
            }
        }
    }

    private function createCompatibilityLayerDefinition(ContainerBuilder $container, string $definitionId, bool $shouldBePsr6): ?Definition
    {
        $definition = $container->getDefinition($definitionId);

        while (! $definition->getClass() && $definition instanceof ChildDefinition) {
            $definition = $container->findDefinition($definition->getParent());
        }

        if ($shouldBePsr6 === is_a($definition->getClass(), CacheItemPoolInterface::class, true)) {
            return null;
        }

        $targetClass   = CacheProvider::class;
        $targetFactory = DoctrineProvider::class;

        if ($shouldBePsr6) {
            $targetClass   = CacheItemPoolInterface::class;
            $targetFactory = CacheAdapter::class;

            trigger_deprecation(
                'doctrine/doctrine-bundle',
                '2.4',
                'Configuring doctrine/cache is deprecated. Please update the cache service "%s" to use a PSR-6 cache.',
                $definitionId
            );
        }

        return (new Definition($targetClass))
            ->setFactory([$targetFactory, 'wrap'])
            ->addArgument(new Reference($definitionId));
    }

    private function wrapIfNecessary(ContainerBuilder $container, ?string $aliasId, string $definitionId, bool $shouldBePsr6): void
    {
        $compatibilityLayer = $this->createCompatibilityLayerDefinition($container, $definitionId, $shouldBePsr6);
        if ($compatibilityLayer === null) {
            return;
        }

        $compatibilityLayerId = $definitionId . '.compatibility_layer';
        if (null !== $aliasId) {
            $container->setAlias($aliasId, $compatibilityLayerId);
        }
        $container->setDefinition($compatibilityLayerId, $compatibilityLayer);
    }
}
