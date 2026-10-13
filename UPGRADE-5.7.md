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
