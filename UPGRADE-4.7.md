UPGRADE FROM 4.6 to 4.7
=======================

## PHP requirements

* The bundle now requires PHP 8.1 or newer. If you're not running PHP 8.1 yet,
  it's recommended that you upgrade to PHP 8.1 before upgrading the bundle.

* The `fixture_loader` configuration option was deprecated and will be removed
  in 5.0.
* The `doctrine_mongodb.odm.fixture_loader` parameter has been removed.
* Implementing `ContainerAwareInterface` on fixtures classes is deprecated,
  use dependency injection instead.
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
