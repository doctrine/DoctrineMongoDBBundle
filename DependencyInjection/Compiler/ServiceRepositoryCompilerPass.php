<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_combine;
use function array_keys;
use function array_map;

final class ServiceRepositoryCompilerPass implements CompilerPassInterface
{
    public const REPOSITORY_SERVICE_TAG = 'doctrine_mongodb.odm.repository_service';

    public function process(ContainerBuilder $container)
    {
        // when ODM is not enabled
        if (! $container->hasDefinition('doctrine_mongodb.odm.container_repository_factory')) {
            return;
        }

        $locatorDef = $container->getDefinition('doctrine_mongodb.odm.container_repository_factory');

        $repoServiceIds = array_keys($container->findTaggedServiceIds(self::REPOSITORY_SERVICE_TAG));

        $repoReferences = array_map(static function ($id) {
            return new Reference($id);
        }, $repoServiceIds);

        $locatorDef->replaceArgument(0, ServiceLocatorTagPass::register($container, array_combine($repoServiceIds, $repoReferences)));
    }
}
