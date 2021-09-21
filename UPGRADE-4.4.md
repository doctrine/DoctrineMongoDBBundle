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
