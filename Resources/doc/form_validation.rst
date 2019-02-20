Form & Validation
=================

DocumentType
------------

TODO

Unique constraint
-----------------

This bundle provides a ``Unique`` constraint, which extends the `UniqueEntity`_
constraint provided by Symfony's Doctrine bridge. This constraint allows you to
validate the uniqueness of an document field against the database.

The ``Unique`` constraint shares the same options as `UniqueEntity`_, which
means that the ``em`` option should be used if you wish to specify the document
manager explicitly instead of having it be inferred from the document class.

.. _`UniqueEntity`: http://symfony.com/doc/current/reference/constraints/UniqueEntity.html
