Registering Event Listeners and Subscribers
===========================================

Doctrine allows you to register listeners and subscribers that are notified
when different events occur inside Doctrine's ODM. For more information,
see Doctrine's `Event Documentation`_.

.. note::

    Each connection in Doctrine has its own event manager, which is shared with
    document managers tied to that connection. Listeners and subscribers may be
    registered with all event managers or just one (using the connection name).

Use the ``doctrine_mongodb.odm.event_listener`` tag to register a listener. The
``event`` attribute is required and should denote the event on which to listen.
By default, listeners will be registered with event managers for all connections.
To restrict a listener to a single connection, specify its name in the tag's
``connection`` attribute.


The ``priority`` attribute, which defaults to ``0`` if omitted, may be used
to control the order in which listeners are registered. Much like Symfony's
`event dispatcher`_, greater number will result in the listener executing
first and listeners with the same priority will be executed in the order that
they were registered with the event manager.

Lastly, the ``lazy`` attribute, which defaults to ``false`` if omitted, may
be used to request that the listener be lazily loaded by the event manager
when its event is dispatched.

Starting with DoctrineMongoDBBundle bundle 4.7, you can use the ``#[AsDocumentListener]``
attribute to tag the service.

.. configuration-block::

    .. code-block:: php-attributes

        // src/App/EventListener/SearchIndexer.php
        namespace App\EventListener;

        use Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener;
        use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

        #[AsDocumentListener('postPersist', priority: 500, connection: 'default')]
        class SearchIndexer
        {
            public function postPersist(LifecycleEventArgs $event): void
            {
                // ...
            }
        }

    .. code-block:: yaml

        # config/services.yaml
        services:
            # ...

            App\EventListener\SearchIndexer:
                tags:
                    -
                        name: 'doctrine_mongodb.odm.event_listener'
                        # this is the only required option for the lifecycle listener tag
                        event: 'postPersist'

                        # listeners can define their priority in case multiple subscribers or listeners are associated
                        # to the same event (default priority = 0; higher numbers = listener is run earlier)
                        priority: 500

                        # you can also restrict listeners to a specific Doctrine connection
                        connection: 'default'

    .. code-block:: xml

        <!-- config/services.xml -->
        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:doctrine="http://symfony.com/schema/dic/doctrine">
            <services>
                <!-- ... -->

                <!--
                    * 'event' is the only required option that defines the lifecycle listener
                    * 'priority': used when multiple subscribers or listeners are associated to the same event
                    *             (default priority = 0; higher numbers = listener is run earlier)
                    * 'connection': restricts the listener to a specific MongoDB connection
                -->
                <service id="App\EventListener\SearchIndexer">
                    <tag name="doctrine_mongodb.odm.event_listener"
                        event="postPersist"
                        priority="500"
                        connection="default" />
                </service>
            </services>
        </container>

.. note::

    Unlike Symfony event listeners, Doctrine's event manager expects each
    listener and subscriber to have a method name corresponding to the observed
    event(s). For this reason, the aforementioned tags have no ``method``
    attribute.

.. _`event dispatcher`: https://symfony.com/doc/current/components/event_dispatcher.html
.. _`Event Documentation`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/events.html
