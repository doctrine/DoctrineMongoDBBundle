Installation
============

This chapter assumes you have Composer installed globally, as explained
in the `installation chapter`_ of the Composer documentation.

.. note::

   The ODM requires the `MongoDB driver`_ (``mongodb``).

Install the bundle with Symfony Flex
------------------------------------

A Flex recipe for the DoctrineMongoDBBundle is provided as a Contrib Recipe.
You need to allow its usage first:

.. code-block:: bash

    composer config extra.symfony.allow-contrib true

.. code-block:: bash

    composer require doctrine/mongodb-odm-bundle

Install the bundle with Composer
--------------------------------

To install DoctrineMongoDBBundle with Composer run the following command:

.. code-block:: bash

    composer require doctrine/mongodb-odm-bundle


Enable the Bundle
-----------------

The bundle should be automatically enabled if you use Flex.
Otherwise, you'll need to manually enable the bundle by adding the
following line in the ``config/bundles.php`` file of your project:

.. code-block:: php

    // config/bundles.php
    return [
        // ...
        Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle::class => ['all' => true],
    ];

Configuration
-------------

Flex recipe will automatically create the ``config/packages/doctrine_mongodb.yaml``
file with default configuration. Without Flex you need to create the file
manually and fill it with some basic configuration that sets up the document manager.
The recommended way is to enable ``auto_mapping``, which will activate
the MongoDB ODM across your application:

.. code-block:: yaml

    # config/services.yaml
    parameters:
        mongodb_server: "mongodb://localhost:27017"

.. code-block:: yaml

    # config/packages/doctrine_mongodb.yaml
    doctrine_mongodb:
        connections:
            default:
                server: "%mongodb_server%"
                options: {}
        default_database: test_database
        document_managers:
            default:
                auto_mapping: true

.. note::

    Please also make sure that the MongoDB server is running in the background.
    For more details, see the MongoDB `Installation Tutorials`_.

.. tip::

    You can configure bundle options that depend on where your application
    is run (e.g. during tests or development) with `Environment Variables`_.

Authentication
--------------

If you use authentication on your MongoDB database, then you can provide username,
password, and authentication database in the following way:

.. code-block:: yaml

    # config/services.yaml
    parameters:
        mongodb_server: "mongodb://username:password@localhost:27017/?authSource=auth-db"

.. note::

    The authentication database is different from the default database used by MongoDB.

.. _`installation chapter`: https://getcomposer.org/doc/00-intro.md
.. _`MongoDB driver`: https://docs.mongodb.com/ecosystem/drivers/php/
.. _`Installation Tutorials`: https://docs.mongodb.com/manual/installation/
.. _`Environment Variables`: https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables
