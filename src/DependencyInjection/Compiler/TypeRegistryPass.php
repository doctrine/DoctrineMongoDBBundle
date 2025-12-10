<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\Attribute\AsFieldType;
use Doctrine\Bundle\MongoDBBundle\Types\LazyTypeRegistry;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function array_reverse;
use function sprintf;

/**
 * Register all types as services in the type registry.
 *
 * @internal
 */
final class TypeRegistryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private const TAG = 'doctrine_mongodb.odm.field_type';

    public static function registerAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsFieldType::class,
            static function (Definition $definition, AsFieldType $attribute): void {
                $definition->addTag(self::TAG, ['type' => $attribute->name, 'object_manager' => $attribute->objectManager]);
            },
        );
    }

    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('doctrine_mongodb.odm.type_registry.abstract')) {
            return;
        }

        $taggedServices = array_reverse($this->findAndSortTaggedServices(self::TAG, $container));
        $typesByManager = [];

        foreach ($taggedServices as $id) {
            foreach ($container->getDefinition((string) $id)->getTag(self::TAG) as $attributes) {
                if (! isset($attributes['type'])) {
                    throw new InvalidArgumentException(sprintf('The service "%s" must define the "type" attribute on "%s" tags.', $id, self::TAG));
                }

                $typesByManager[$attributes['object_manager'] ?? ''][$attributes['type']] = (string) $id;
            }
        }

        foreach ($container->getParameter('doctrine_mongodb.odm.document_managers') as $managerName => $managerDefinitionId) {
            $serviceMap = [...($typesByManager[''] ?? []), ...($typesByManager[$managerName] ?? [])];
            if (! $serviceMap) {
                continue;
            }

            $serviceLocator = (new Definition(ServiceLocator::class))->setArguments([$serviceMap]);
            $container->getDefinition(sprintf('doctrine_mongodb.odm.%s_type_registry', $managerName))
                ->setClass(LazyTypeRegistry::class)
                ->setArguments([$serviceLocator]);
        }
    }
}
