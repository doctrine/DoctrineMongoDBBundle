UPGRADE FROM 3.x to 4.0
=======================

* The bundle now requires MongoDB ODM 2.0. You should have a look at its UPGRADE
  document and update your application accordingly.
* The `default_repository_class` configuration option was dropped. Use the 
  `default_document_repository_class` option instead.
* The `retry_connect` and `retry_query` configuration options were dropped
  without replacement.
* The YAML metadata driver was dropped without replacement. 
* The `doctrine:mongodb:generate:documents` and
  `doctrine:mongodb:generate:repositories` commands have been dropped without
  replacement.
* The `doctrine.odm.mongodb.document_manager` service was removed. Use the
  `doctrine_mongodb.odm.document_manager` service instead.
