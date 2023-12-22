UPGRADE FROM 4.x to 5.0
=======================

## PHP version and dependencies

* Add support for Symfony 7.0 and require at least Symfony 6.4
* `doctrine/mongodb-odm` 2.6 and `doctrine/persistence` 3.0 are required

## Annotations

* Remove support of Annotation mapping, you should use Attributes or XML instead.

## Commands

* All command and compiler pass classes are internal and final. They cannot be
  used directly or extended.
* The `doctrine:mongodb:tail-cursor` command and
  `Doctrine\Bundle\MongoDBBundle\Cursor\TailableCursorProcessorInterface`
  interface have been dropped. You should use
  [change streams](https://docs.mongodb.com/manual/changeStreams/) instead.
* The `setContainer`, `getContainer`, `getDoctrineDocumentManagers`,
  `findBundle` and `findBasePathForBundle` methods from
  `Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand` have been
  removed without replacement.

## Configuration

Remove all `doctrine_mongodb.odm.*` parameters.

Deprecated options have been removed:

| namespace                | removed     | replaced by        |
|--------------------------|-------------|--------------------|
| `default_commit_options` | `fsync`     | `j`                |
| `default_commit_options` | `safe`      | `w`                |
| `connections.*.options`  | `fsync`     | `journal`          |
| `connections.*.options`  | `slaveOkay` | `readPreference`   |
| `connections.*.options`  | `timeout`   | `connectTimeoutMS` |
| `connections.*.options`  | `wTimeout`  | `wTimeoutMS`       |

## Event Subscriber

* Remove `Doctrine\Bundle\MongoDBBundle\EventSubscriber\EventSubscriberInterface`.
  Use the `#[AsDocumentListener]` attribute instead.
* Remove parameters `$method` and `$lazy` of `#[AsDocumentListener]`, they are
  not used.

## Fixtures

* Remove `--service` option from `doctrine:mongodb:fixtures:load` command
* Remove automatic injection of the container in fixtures classes implementing
  `ContainerAwareInterface`. You should use dependency injection instead.
* Remove the `fixture_loader` configuration

## Cache

The `Doctrine\Common\Cache\` providers are not supported anymore. The configuration
uses `Symfony\Component\Cache\Adapter\` providers instead, for PSR-6 compatibility.

If you want to use redis or memcached, the configuration of `host`, `port` and `instance_class`
must be done for each document manager. The parameters `doctrine_mongodb.odm.cache.memcached_*`
and `doctrine_mongodb.odm.cache.redis_*` are not read anymore.
