<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\Attribute\AsFieldType;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use ReflectionClass;
use Reflector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function array_replace;
use function class_exists;
use function sprintf;

/**
 * Register all types as services in the type provider.
 *
 * @internal
 */
final class TypeProviderPass implements CompilerPassInterface
{
    private const TAG = 'doctrine_mongodb.odm.field_type';

    private const ALL = '*';

    public static function registerAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsFieldType::class,
            static function (Definition $definition, AsFieldType $attribute, Reflector $reflector): void {
                // AsFieldType targets classes, so the reflector is always the tagged class.
                $definition->addTag(self::TAG, ['type' => $attribute->type ?? ($reflector instanceof ReflectionClass ? $reflector->getName() : $definition->getClass()), 'document_manager' => $attribute->documentManager]);
            },
        );
    }

    public function process(ContainerBuilder $container): void
    {
        $configTypes = [];
        if ($container->hasParameter('doctrine_mongodb.odm.custom_types')) {
            /** @var array<string, array{class: string}> $configTypes */
            $configTypes = $container->getParameter('doctrine_mongodb.odm.custom_types');
            $container->getParameterBag()->remove('doctrine_mongodb.odm.custom_types');
        }

        $taggedServiceIds = $container->findTaggedServiceIds(self::TAG);
        if ($taggedServiceIds === []) {
            // Configured types are loaded into the shared registry through the manager
            // configurator, so no scoped registry is needed.
            // Ensure backward compatibility for projects that use the static Type::register()
            // to register custom types in the global TypeRegistry instance.
            return;
        }

        // The TypeRegistry and the type provider it relies on were introduced together
        // in doctrine/mongodb-odm 2.18.
        if (! class_exists(TypeRegistry::class)) {
            throw new LogicException('MongoDB field types services not supported. Upgrade to doctrine/mongodb-odm >= 2.18');
        }

        /** @var array<string, array<string, Reference|Definition>> $typesByManager */
        $typesByManager = [
            self::ALL => [],
        ];
        foreach ($configTypes as $typeName => $typeConfig) {
            $typesByManager[self::ALL][$typeName] = new Definition($typeConfig['class']);
        }

        // Service-tagged types apply either to every document manager or to a named one.
        foreach ($taggedServiceIds as $id => $tags) {
            foreach ($tags as $attributes) {
                if (! isset($attributes['type'])) {
                    throw new InvalidArgumentException(sprintf('The service "%s" must define the "type" parameter on "%s" tags.', $id, self::TAG));
                }

                $typesByManager[$attributes['document_manager'] ?? self::ALL][$attributes['type']] = new Reference($id);
            }
        }

        /** @var array<string, string> $documentManagers */
        $documentManagers = $container->getParameter('doctrine_mongodb.odm.document_managers');
        foreach (array_keys($documentManagers) as $managerName) {
            $services = array_replace($typesByManager[self::ALL], $typesByManager[$managerName] ?? []);
            if (! $services) {
                continue;
            }

            // Inject a ServiceLocator so types are resolved lazily on first use. The locator is
            // keyed by type name, so it is used directly as a service provider.
            $locatorRef = ServiceLocatorTagPass::register($container, $services);
            $registryId = sprintf('doctrine_mongodb.odm.%s_type_provider', $managerName);
            $container->setDefinition($registryId, new Definition(TypeRegistry::class, [$locatorRef]));

            // Expose the scoped registry to that document manager's Configuration.
            $container
                ->getDefinition(sprintf('doctrine_mongodb.odm.%s_configuration', $managerName))
                ->addMethodCall('setTypeProvider', [new Reference($registryId)]);
        }
    }
}
