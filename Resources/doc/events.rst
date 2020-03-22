Registering Event Listeners and Subscribers
===========================================

Doctrine allows you to register listeners and subscribers that are notified
when different events occur inside Doctrine's ODM. For more information,
see Doctrine's `Event Documentation`_.

.. note::

    Each connection in Doctrine has its own event manager, which is shared with
    document managers tied to that connection. Listeners and subscribers may be
    registered with all event managers or just one (using the connection name).

In Symfony, you can register a listener or subscriber by creating a service
and then `tagging`_ it with a specific tag.

Event Listeners
---------------

Use the ``doctrine_mongodb.odm.event_listener`` tag to
register a listener. The ``event`` attribute is required and should denote
the event on which to listen. By default, listeners will be registered with
event managers for all connections. To restrict a listener to a single
connection, specify its name in the tag's ``connection`` attribute.

The ``priority`` attribute, which defaults to ``0`` if omitted, may be used
to control the order in which listeners are registered. Much like Symfony's
`event dispatcher`_, greater number will result in the listener executing
first and listeners with the same priority will be executed in the order that
they were registered with the event manager.

Lastly, the ``lazy`` attribute, which defaults to ``false`` if omitted, may
be used to request that the listener be lazily loaded by the event manager
when its event is dispatched.

.. configuration-block::

    .. code-block:: yaml

        services:
            my_doctrine_listener:
                class:   App\Listener\MyDoctrineListener
                # ...
                tags:
                    -  { name: doctrine_mongodb.odm.event_listener, event: postPersist }

    .. code-block:: xml

        <service id="my_doctrine_listener" class="App\Listener\MyDoctrineListener">
            <!-- ... -->
            <tag name="doctrine_mongodb.odm.event_listener" event="postPersist" />
        </service>

    .. code-block:: php

        $definition = new Definition('App\Listener\MyDoctrineListener');
        // ...
        $definition->addTag('doctrine_mongodb.odm.event_listener', [
            'event' => 'postPersist',
        ]);
        $container->setDefinition('my_doctrine_listener', $definition);

Event Subscribers
-----------------

Implement ``Doctrine\Bundle\MongoDBBundle\EventSubscriber\EventSubscriberInterface``
and `autoconfiguration`_ to automatically register your class as a MongoODM
event subscriber.

.. code-block:: php

    // src/App/EventSubscriber/MongoDB/ProductSubscriber.php
    namespace App\EventSubscriber\MongoDB;

    use App\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\EventSubscriber\EventSubscriberInterface;

    class ProductSubscriber implements EventSubscriberInterface
    {
        public function getSubscribedEvents()
        {
            return [
                // List events to subscribe
            ];
        }
    }

.. configuration-block::

    .. code-block:: yaml

        # config/services.yaml
        services:

            App\EventSubscriber\MongoDB\:
                resource: '../src/EventSubscriber/MongoDB/*'
                autoconfigure: true

    .. code-block:: xml

        <!-- config/services.xml -->
        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services
                https://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <prototype namespace="App\EventSubscriber\MongoDB\" resource="../src/EventSubscriber/MongoDB/*" autoconfigure="true" />
            </services>
        </container>

Alternatively, use the ``doctrine_mongodb.odm.event_subscriber`` tag
to register a subscriber. Subscribers must implement the
``Doctrine\Common\EventSubscriber`` interface, which means that they must
contain method returning the events they will observe. For this reason,
this tag has no ``event`` attribute, however the ``connection``,
``priority`` and ``lazy`` attributes are available.

.. code-block:: php

    // src/App/EventSubscriber/MongoDB/ProductSubscriber.php
    namespace App\EventSubscriber\MongoDB;

    use App\Document\Product;
    use Doctrine\Common\EventSubscriber;

    class ProductSubscriber implements EventSubscriber
    {
        public function getSubscribedEvents()
        {
            return [
                // List events to subscribe
            ];
        }
    }

.. configuration-block::

    .. code-block:: yaml

        # config/services.yaml
        services:

            App\EventSubscriber\MongoDB\:
                resource: '../src/EventSubscriber/MongoDB/*'
                tags:
                    - { name: doctrine_mongodb.odm.event_subscriber }

    .. code-block:: xml

        <!-- config/services.xml -->
        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services
                https://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <prototype namespace="App\EventSubscriber\MongoDB\" resource="../src/EventSubscriber/MongoDB/*">
                    <tag name="doctrine_mongodb.odm.event_subscriber" />
                </prototype>
            </services>
        </container>

.. note::

    Unlike Symfony event listeners, Doctrine's event manager expects each
    listener and subscriber to have a method name corresponding to the observed
    event(s). For this reason, the aforementioned tags have no ``method``
    attribute.

.. _`event dispatcher`: https://symfony.com/doc/current/components/event_dispatcher.html
.. _`Event Documentation`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/events.html
.. _`tagging`: https://symfony.com/doc/current/service_container/tags.html
.. _`autoconfiguration`: https://symfony.com/doc/current/service_container.html#the-autoconfigure-option
