UPGRADE FROM 4.x to 4.4
=======================

* The `doctrine:mongodb:tail-cursor` command and
  `Doctrine\Bundle\MongoDBBundle\Cursor\TailableCursorProcessorInterface`
  interface have been deprecated. You should use
  [change streams](https://docs.mongodb.com/manual/changeStreams/) instead.
