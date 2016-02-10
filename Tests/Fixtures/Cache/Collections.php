<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Collections
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(collectionClass="SomeCollection") */
    public $coll;

    /** @ODM\ReferenceMany(collectionClass="SomeCollection") */
    public $refs;

    /** @ODM\EmbedMany(collectionClass="AnotherCollection") */
    public $another;
}

class SomeCollection extends ArrayCollection {}

class AnotherCollection extends ArrayCollection {}
