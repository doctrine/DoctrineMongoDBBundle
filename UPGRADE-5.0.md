UPGRADE FROM 4.x to 5.0
=======================

* Add support for Symfony 7.0 and require at least Symfony 6.4
* The `doctrine:mongodb:tail-cursor` command and
  `Doctrine\Bundle\MongoDBBundle\Cursor\TailableCursorProcessorInterface`
  interface have been dropped. You should use
  [change streams](https://docs.mongodb.com/manual/changeStreams/) instead.
* The `setContainer`, `getContainer`, `getDoctrineDocumentManagers`,
  `findBundle` and `findBasePathForBundle` methods from
  `Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand` have been
  removed without replacement.
* All command and compiler pass classes are internal and final. They cannot be
  used directly or extended.
* Remove support of Annotation mapping, you should use Attributes or XML instead.

## Fixtures

* Remove `--service` option from `doctrine:mongodb:fixtures:load` command
* Remove automatic injection of the container in fixtures classes implementing
  `ContainerAwareInterface`. You should use dependency injection instead.
* Remove the `fixture_loader` configuration

## Cache

The `Doctrine\Common\Cache\` providers are not supported anymore. The configuration
uses `Symfony\Component\Cache\Adapter\` providers instead, for PSR-6 compatibility.

The recommended configuration for production is `doctrine_mongodb.system_cache_pool`:

```yaml
when@prod:
    doctrine_mongodb:
        document_managers:
            default:
                metadata_cache_driver:
                    type: service
                    id: doctrine_mongodb.system_cache_pool
```

If you still want to use redis or memcached, the configuration of the host and port
must be done for each document manager. The parameters `doctrine_mongodb.odm.cache.memcached_*`
and `doctrine_mongodb.odm.cache.redis_*` are not read anymore.
