UPGRADE FROM 4.6 to 4.7
=======================

## PHP requirements

* The bundle now requires PHP 8.1 or newer. If you're not running PHP 8.1 yet,
  it's recommended that you upgrade to PHP 8.1 before upgrading the bundle.

## Event Subscriber

* `Doctrine\Bundle\MongoDBBundle\EventSubscriber\EventSubscriberInterface` has
  been deprecated. Use the `#[AsDocumentListener]` attribute instead.

## Fixtures

* The `fixture_loader` configuration option was deprecated and will be removed
  in 5.0.
* The `doctrine_mongodb.odm.fixture_loader` parameter has been removed.
* Implementing `ContainerAwareInterface` on fixtures classes is deprecated,
  use dependency injection instead.

## Configuration

Deprecate configuration settings:

| namespace                | removed     | replaced by        |
|--------------------------|-------------|--------------------|
| `default_commit_options` | `fsync`     | `j`                |
| `default_commit_options` | `safe`      | `w`                |
| `connections.*.options`  | `fsync`     | `journal`          |
| `connections.*.options`  | `slaveOkay` | `readPreference`   |
| `connections.*.options`  | `timeout`   | `connectTimeoutMS` |
| `connections.*.options`  | `wTimeout`  | `wTimeoutMS`       |

* Deprecated the following `*.class` parameters, you should use a compiler pass to update the service instead:
  * `doctrine_mongodb.odm.connection.class`
  * `doctrine_mongodb.odm.configuration.class`
  * `doctrine_mongodb.odm.document_manager.class`
  * `doctrine_mongodb.odm.manager_configurator.class`
  * `doctrine_mongodb.odm.event_manager.class`
  * `doctrine_odm.mongodb.validator_initializer.class`
  * `doctrine_odm.mongodb.validator.unique.class`
  * `doctrine_mongodb.odm.class`
  * `doctrine_mongodb.odm.security.user.provider.class`
  * `doctrine_mongodb.odm.proxy_cache_warmer.class`
  * `doctrine_mongodb.odm.hydrator_cache_warmer.class`
  * `doctrine_mongodb.odm.persistent_collection_cache_warmer.class`
* Deprecated modifying the following parameters, they are used internally:
  * `doctrine_mongodb.odm.cache.array.class`
  * `doctrine_mongodb.odm.cache.apc.class`
  * `doctrine_mongodb.odm.cache.apcu.class`
  * `doctrine_mongodb.odm.cache.memcache.class`
  * `doctrine_mongodb.odm.cache.memcache_host`
  * `doctrine_mongodb.odm.cache.memcache_port`
  * `doctrine_mongodb.odm.cache.memcache_instance.class`
  * `doctrine_mongodb.odm.cache.xcache.class`
  * `doctrine_mongodb.odm.metadata.driver_chain.class`
  * `doctrine_mongodb.odm.metadata.attribute.class`
  * `doctrine_mongodb.odm.metadata.xml.class`
