UPGRADE FROM 4.x to 5.0
=======================

* The `doctrine:mongodb:tail-cursor` command and
  `Doctrine\Bundle\MongoDBBundle\Cursor\TailableCursorProcessorInterface`
  interface have been dropped. You should use
  [change streams](https://docs.mongodb.com/manual/changeStreams/) instead.
* The `setContainer`, `getContainer`, `getDoctrineDocumentManagers`,
  `findBundle` and `findBasePathForBundle` methods from
  `Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand` have been
  removed without replacement.
* The `Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand` class is now
  `@internal`, you should not extend from this class.