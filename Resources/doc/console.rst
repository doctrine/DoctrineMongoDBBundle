Console Commands
================

The Doctrine2 ODM integration offers several console commands under the
``doctrine:mongodb`` namespace. To view the command list you can run the console
without any arguments:

.. code-block:: bash

    php bin/console

A list of available command will print out, many of which start with the
``doctrine:mongodb`` prefix. You can find out more information about any
of these commands (or any Symfony command) by running the ``help`` command.
For example, to get details about the ``doctrine:mongodb:query`` task, run:

.. code-block:: bash

    php bin/console help doctrine:mongodb:query

.. note::

   To be able to load data fixtures into MongoDB, you will need to have the
   ``DoctrineFixturesBundle`` bundle installed. To learn how to do it, read
   the "`DoctrineFixturesBundle`_" entry of the documentation.

.. _`DoctrineFixturesBundle`: http://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html
