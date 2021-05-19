UPGRADE FROM 4.x to 4.4
=======================

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
