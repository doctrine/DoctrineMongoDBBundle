<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Handles the registration of event listeners and subscribers based on tags.
 *
 * To register an event *subscriber* to the "default" document manager, you
 * should apply one of the following tags to your service:
 *
 *  * doctrine.common.event_subscriber              (will subscribe to all document managers)
 *  * doctrine.odm.mongodb.default_event_subscriber (subscribes only on the "default" dm)
 *
 * To register an event *listener* to the "default" document manager, you
 * should apply one of the following tags to your service:
 *
 *  * doctrine.common.event_listener                (will be a listener on all document managers)
 *  * doctrine.odm.mongodb.default_event_listener   (listens only on the "default" dm)
 *
 * The tag for a listener should include an "event" key, which specifies
 * which event to listen on.
 */
class RegisterEventListenersAndSubscribersPass implements CompilerPassInterface
{
    protected $container;

    public function process(ContainerBuilder $container)
    {
        $this->container = $container;
        foreach ($container->findTaggedServiceIds('doctrine.odm.mongodb.event_manager') as $id => $tag) {
            $definition = $container->getDefinition($id);
            $prefix = substr($id, 0, -1 * strlen('_event_manager'));
            $this->registerListeners($prefix, $definition);
            $this->registerSubscribers($prefix, $definition);
        }
    }

    /**
     * Registers event subscribers by searching for services tagged with
     * a document manager-specific tag (e.g. "doctrine.odm.mongodb.default_event_subscriber")
     * and a global tag ("doctrine.common.event_subscriber").
     *
     * @param  string $prefix The prefix name used to construct the subscriber tag name
     * @param  Definition $definition The event manager Definition to attach to
     * @return void
     */
    protected function registerSubscribers($prefix, $definition)
    {
        $subscribers = array_merge(
            $this->container->findTaggedServiceIds('doctrine.common.event_subscriber'),
            $this->container->findTaggedServiceIds($prefix.'_event_subscriber')
        );

        foreach ($subscribers as $id => $instances) {
            $definition->addMethodCall('addEventSubscriber', array(new Reference($id)));
        }
    }

    /**
     * Registers event listeners by searching for services tagged with
     * a document manager-specific tag (e.g. "doctrine.odm.mongodb.default_event_listener")
     * and a global tag ("doctrine.common.event_listener").
     *
     * @param  string $prefix The prefix name used to construct the listener tag name
     * @param  Definition $definition The event manager Definition to attach to
     * @return void
     */
    protected function registerListeners($prefix, $definition)
    {
        $listeners = array_merge(
            $this->container->findTaggedServiceIds('doctrine.common.event_listener'),
            $this->container->findTaggedServiceIds($prefix.'_event_listener')
        );

        foreach ($listeners as $listenerId => $instances) {
            $events = array();
            foreach ($instances as $attributes) {
                if (isset($attributes['event'])) {
                    $events[] = $attributes['event'];
                }
            }

            if (0 < count($events)) {
                $definition->addMethodCall('addEventListener', array(
                    $events,
                    new Reference($listenerId),
                ));
            }
        }
    }
}
