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
* All command and compiler pass classes are internal and final. They cannot be
  used directly or extended.
* Remove support of Annotation mapping, you should use Attributes or XML instead.
* Remove `--service` option from `doctrine:mongodb:fixtures:load` command
