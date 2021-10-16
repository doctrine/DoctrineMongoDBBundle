UPGRADE FROM 4.x to 4.4
=======================

* The `doctrine:mongodb:tail-cursor` command and
  `Doctrine\Bundle\MongoDBBundle\Cursor\TailableCursorProcessorInterface`
  interface have been deprecated. You should use
  [change streams](https://docs.mongodb.com/manual/changeStreams/) instead.
* The `setContainer`, `getContainer`, `getDoctrineDocumentManagers`,
  `findBundle` and `findBasePathForBundle` methods from
  `Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand` have been
  deprecated without replacement.
* The `Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand` class has
  been marked as `@internal`, you should not extend from this class.
* The `doctrine_mongodb.odm.command_logger` service has been deprecated. You should use
  `doctrine_mongodb.odm.psr_command_logger` instead.
* The `metadata_cache_driver` configuration was deprecated. The bundle
  automatically registers an `ArrayAdapter` or a `PhpArrayAdapter` based on the
  `kernel.debug` flag.
* Due to the deprecation of doctrine/cache, the bundle will attempt to create
  the requested cache. For the `apcu`, `array`, `memcached`, `redis`, and
  `service` drivers we are able to do this transparently. For other adapters,
  you may need to add an explicit dependency on `doctrine/cache` 1.11 to ensure
  you still have the desired cache. However, you should be able to drop the
  cache configuration entirely and take advantage of the automatic
  configuration.
