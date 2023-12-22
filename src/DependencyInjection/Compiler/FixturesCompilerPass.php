<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/** @internal */
final class FixturesCompilerPass implements CompilerPassInterface
{
    public const FIXTURE_TAG = 'doctrine.fixture.odm.mongodb';

    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('doctrine_mongodb.odm.symfony.fixtures.loader')) {
            return;
        }

        $definition     = $container->getDefinition('doctrine_mongodb.odm.symfony.fixtures.loader');
        $taggedServices = $container->findTaggedServiceIds(self::FIXTURE_TAG);

        $fixtures = [];
        foreach ($taggedServices as $serviceId => $tags) {
            $groups = [];
            foreach ($tags as $tagData) {
                if (! isset($tagData['group'])) {
                    continue;
                }

                $groups[] = $tagData['group'];
            }

            $fixtures[] = [
                'fixture' => new Reference($serviceId),
                'groups' => $groups,
            ];
        }

        $definition->addMethodCall('addFixtures', [$fixtures]);
    }
}
