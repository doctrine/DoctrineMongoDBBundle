DoctrineMongoDBBundle
=====================

The `MongoDB`_ Object Document Mapper (ODM) is much like the Doctrine2 ORM
in its philosophy and how it works. In other words, like the `Doctrine2 ORM`_,
with the Doctrine ODM, you deal only with plain PHP objects, which are then
persisted transparently to and from MongoDB.

.. tip::

    You can read more about the Doctrine MongoDB ODM via the project's `documentation`_.

The bundle integrates the Doctrine MongoDB ODM into Symfony,
helping you to configure and use it in your application.

.. note::

    This documentation will feel a lot like the `Doctrine2 ORM chapter`_,
    which talks about how the Doctrine ORM can be used to persist data to
    relational databases (e.g. MySQL). This is on purpose - whether you persist
    to a relational database via the ORM or to MongoDB via the ODM, the philosophies
    are very much the same.

.. toctree::

   installation
   config
   first_steps
   form_validation
   security_bundle
   messenger
   events
   console
   cookbook/registration_form

Doctrine Extensions: Timestampable, Sluggable, etc.
---------------------------------------------------

Doctrine is quite flexible, and a number of third-party extensions are available
that allow you to perform repeated and common tasks on your entities.
These include things such as *Sluggable*, *Timestampable*, *Loggable*, *Translatable*,
and *Tree*.

For more information about them, see `available Doctrine extensions`_
and use the `StofDoctrineExtensionsBundle`_ to integrate them in your application.

.. _`MongoDB`: https://www.mongodb.com
.. _`Doctrine2 ORM`: https://symfony.com/doc/current/book/doctrine.html
.. _`documentation`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/
.. _`Doctrine2 ORM chapter`: https://symfony.com/doc/current/book/doctrine.html
.. _`available Doctrine extensions`: https://github.com/Atlantic18/DoctrineExtensions
.. _`StofDoctrineExtensionsBundle`: https://symfony.com/doc/current/bundles/StofDoctrineExtensionsBundle/index.html
