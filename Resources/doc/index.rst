DoctrineMongoDBBundle
=====================

The `MongoDB`_ Object Document Mapper (ODM) is much like the Doctrine2 ORM
in its philosophy and how it works. In other words, like the `Doctrine2 ORM`_,
with the Doctrine ODM, you deal only with plain PHP objects, which are then
persisted transparently to and from MongoDB.

.. tip::

    You can read more about the Doctrine MongoDB ODM via the project's `documentation`_.

A bundle is available that integrates the Doctrine MongoDB ODM into Symfony,
making it easy to configure and use.

.. note::

    This documentation will feel a lot like the `Doctrine2 ORM chapter`_,
    which talks about how the Doctrine ORM can be used to persist data to
    relational databases (e.g. MySQL). This is on purpose - whether you persist
    to a relational database via the ORM or MongoDB via the ODM, the philosophies
    are very much the same.

.. toctree::

   installation
   config
   first_steps
   form_validation
   security_bundle
   events
   console
   cookbook/registration_form

Doctrine Extensions: Timestampable, Sluggable, etc.
---------------------------------------------------

Doctrine is quite flexible, and a number of third-party extensions are available
that allow you to easily perform repeated and common tasks on your entities.
These include thing such as *Sluggable*, *Timestampable*, *Loggable*, *Translatable*,
and *Tree*.

For more information on how to find and use these extensions, see the cookbook
article about `using common Doctrine extensions`_.

.. _`MongoDB`:          http://www.mongodb.org/
.. _`Doctrine2 ORM`: http://symfony.com/doc/current/book/doctrine.html
.. _`documentation`:    http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/
.. _`Doctrine2 ORM chapter`: http://symfony.com/doc/current/book/doctrine.html
.. _`using common Doctrine extensions`: http://symfony.com/doc/current/cookbook/doctrine/common_extensions.html
