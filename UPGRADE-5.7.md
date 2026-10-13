UPGRADE from 5.6 to 5.7
=======================

Ordering document listeners
---------------------------

The `doctrine_mongodb.odm.event_listener` tag accepts new `before` and `after`
options to order the listeners of the same event, as service ids or classes.
The `#[AsDocumentListener]` attribute exposes the same `before` and `after`
arguments:

```php
#[AsDocumentListener('postPersist', after: AuditLogger::class)]
class SearchIndexer
```

These options require `symfony/doctrine-bridge` 8.2 or higher. With older
versions, the tag ignores them and the attribute throws an exception.

This is a new feature. It does not break backward compatibility and does not
deprecate anything.

Lazy document managers
----------------------

Document managers are now lazy services. The
`doctrine_mongodb.odm.document_manager` service, and the `DocumentManager`
injected in your services, is created only when it is first used. This avoids
opening a connection and loading the metadata when the manager is not used.

`ManagerRegistry::getManagerForClass()` uses the metadata drivers to find the
manager of a class. It does not initialize the document managers anymore.

The lazy manager is a proxy that extends `DocumentManager`. Type checks with
`instanceof` keep working, but `get_class()` returns the generated proxy class
instead of `DocumentManager`.

This is a performance improvement. It does not deprecate anything.

Document value resolver
-----------------------

The `Doctrine\Bundle\MongoDBBundle\ArgumentResolver\DocumentValueResolver` class
is deprecated. The `doctrine_mongodb.odm.document_value_resolver` service now
uses the `EntityValueResolver` of `symfony/doctrine-bridge` directly, and the
intermediate `doctrine_mongodb.odm.entity_value_resolver` service is removed.

The `controller.argument_value_resolver` tag name of this service changed from
the `DocumentValueResolver` class name to the
`doctrine_mongodb.odm.document_value_resolver` service id. The default `resolver`
option of the `#[MapDocument]` attribute follows this change. A controller that
explicitly sets `resolver: DocumentValueResolver::class` must set
`resolver: 'doctrine_mongodb.odm.document_value_resolver'` instead, or remove the
option.
