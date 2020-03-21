Form & Validation
=================

DocumentType
------------

This bundle provides a ``DocumentType`` to be used with the Symfony's Form
component. As ``DocumentType`` extends the `EntityType`_ provided by Symfony's
Doctrine bridge you can use all options available for the ORM users. The only
difference is that ``DocumentType`` expects the ``DocumentManager`` name or
instance passed with the ``document_manager`` option instead of ``em``.

Unique constraint
-----------------

This bundle provides a ``Unique`` constraint, which extends the `UniqueEntity`_
constraint provided by Symfony's Doctrine bridge. This constraint allows you to
validate the uniqueness of an document field against the database.

The ``Unique`` constraint shares the same options as `UniqueEntity`_, which
means that the ``em`` option should be used if you wish to specify the document
manager explicitly instead of having it be inferred from the document class.

.. _`EntityType`: https://symfony.com/doc/current/reference/forms/types/entity.html
.. _`UniqueEntity`: https://symfony.com/doc/current/reference/constraints/UniqueEntity.html
