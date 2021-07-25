UPGRADE FROM 4.x to 5.0
=======================

* The `metadata_cache_driver` configuration was dropped. The bundle
  automatically registers an `ArrayAdapter` or a `PhpArrayAdapter` based on the
  `kernel.debug` flag.