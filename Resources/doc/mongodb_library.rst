Using the MongoDB Library
=========================

Doctrine provides a thin wrapper for the PHP MongoDB client class which can conveniently
be fetched from the Symfony service container. A similar example like the tutorial in the
`PHP Documentation`_ might look something like this:

.. code-block:: php

    // connect
    $m = $this->container->get('doctrine_mongodb.odm.default_connection');

    // select a database
    $db = $m->selectDatabase('comedy');

    // select a collection (analogous to a relational database's table)
    $collection = $db->createCollection('cartoons');

    // add a record
    $document = array( "title" => "Calvin and Hobbes", "author" => "Bill Watterson" );
    $collection->insert($document);

    // find everything in the collection
    $cursor = $collection->find();

Reusing Mongo connection
------------------------

If you find yourself in need of ``\Mongo`` class instance i.e. to `store sessions`_
in your Mongo database you may fetch it by defining following services:

.. configuration-block::

    .. code-block:: yaml

        services:
            mongo.connection:
                class: Doctrine\MongoDB\Connection
                factory: ["@doctrine.odm.mongodb.document_manager", getConnection]
                calls:
                    - [initialize, []]
            mongo:
                class: Mongo
                factory: ["@mongo.connection", getMongo]

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <service id="mongo.connection" class="Doctrine\MongoDB\Connection">
                    <factory service="doctrine.odm.mongodb.document_manager" method="getConnection" />
                    <call method="initialize" />
                </service>
                <service id="mongo" class="Doctrine\MongoDB\Connection">
                    <factory service="mongo.connection" method="getMongo" />
                </service>
            </services>
        </container>

.. _`PHP Documentation`: http://www.php.net/manual/en/mongo.tutorial.php
.. _`store sessions`: http://symfony.com/doc/current/cookbook/doctrine/mongodb_session_storage.html
