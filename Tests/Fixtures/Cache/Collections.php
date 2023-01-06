<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Collections
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(collectionClass=SomeCollection::class)
     *
     * @var SomeCollection
     */
    public $coll;

    /**
     * @ODM\ReferenceMany(collectionClass=SomeCollection::class)
     *
     * @var SomeCollection
     */
    public $refs;

    /**
     * @ODM\EmbedMany(collectionClass=AnotherCollection::class)
     *
     * @var AnotherCollection
     */
    public $another;
}

/** @template-extends ArrayCollection<array-key, mixed> */
class SomeCollection extends ArrayCollection
{
}

/** @template-extends ArrayCollection<array-key, mixed> */
class AnotherCollection extends ArrayCollection
{
}
