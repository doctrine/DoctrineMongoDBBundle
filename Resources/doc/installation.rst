Installation
============

This chapter assumes you have Composer installed globally, as explained
in the `installation chapter`_ of the Composer documentation.

.. note::

   The ODM requires the `MongoDB driver`_ (``mongodb``).

Install the bundle with Symfony Flex
------------------------------------

A Flex recipe for the DoctrineMongoDBBundle is provided through a Contrib Recipe
therefore you need to allow its usage:

.. code-block:: bash

    composer config extra.symfony.allow-contrib true

.. code-block:: bash

    composer require doctrine/mongodb-odm-bundle

Install the bundle with Composer
--------------------------------

To install DoctrineMongoDBBundle with Composer just run the following command:

.. code-block:: bash

    composer require doctrine/mongodb-odm-bundle

All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

.. code-block:: php

    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
        ];

        // ...
    }

Configuration
-------------

To get started, you'll need some basic configuration that sets up the document
manager. The easiest way is to enable ``auto_mapping``, which will activate
the MongoDB ODM across your application:

.. code-block:: yaml

    # app/config/parameters.yml
    parameters:
        mongodb_server: "mongodb://localhost:27017"

.. code-block:: yaml

    # app/config/config.yml
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


Authentication
--------------

If you use authentication on your MongoDB database you can the provide username, 
password, and authentication database in the following way:

    # app/config/parameters.yaml
    parameters:
        mongodb_server: "mongodb://username:password@localhost:27017/?authSource=auth-db"

.. note::

    The authentication database is different to the default database used by MongoDB.

.. _`installation chapter`: https://getcomposer.org/doc/00-intro.md
.. _`MongoDB driver`: https://docs.mongodb.com/ecosystem/drivers/php/
.. _`Installation Tutorials`: https://docs.mongodb.com/manual/installation/
.. _`Environment Variables`: https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables
