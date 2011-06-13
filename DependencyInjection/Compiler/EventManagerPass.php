<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EventManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('doctrine.odm.mongodb.event_manager') as $id => $tags) {
            // trim _event_manager suffix
            $prefix = substr($id, 0, -14);
            $evm = $container->getDefinition($id);
            $this->registerListeners($container, $prefix, $evm);
            $this->registerSubscribers($container, $prefix, $evm);
        }
    }

    private function registerSubscribers(ContainerBuilder $container, $prefix, Definition $evm)
    {
        $subscribers = array_replace(
            $container->findTaggedServiceIds('doctrine.common.event_subscriber'),
            $container->findTaggedServiceIds($prefix.'_event_subscriber')
        );

        foreach ($subscribers as $id => $tags) {
            $evm->addMethodCall('addEventSubscriber', array(new Reference($id)));
        }
    }

    private function registerListeners(ContainerBuilder $container, $prefix, Definition $evm)
    {
        $listeners = array_replace(
            $container->findTaggedServiceIds('doctrine.common.event_listener'),
            $container->findTaggedServiceIds($prefix.'_event_listener')
        );

        foreach ($listeners as $id => $tags) {
            $events = array();
            foreach ($tags as $tag) {
                if (isset($tag['event'])) {
                    $events[] = $tag['event'];
                }
            }

            if (0 < count($events)) {
                $evm->addMethodCall('addEventListener', array($events, new Reference($id)));
            }
        }
    }
}
