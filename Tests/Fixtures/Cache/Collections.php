<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

#[ODM\Document]
class Collections
{
    #[ODM\Id]
    public ?ObjectId $id = null;

    #[ODM\EmbedMany(collectionClass: SomeCollection::class)]
    public SomeCollection $coll;

    #[ODM\ReferenceMany(collectionClass: SomeCollection::class)]
    public SomeCollection $refs;

    #[ODM\EmbedMany(collectionClass: AnotherCollection::class)]
    public AnotherCollection $another;
}

/** @template-extends ArrayCollection<array-key, mixed> */
class SomeCollection extends ArrayCollection
{
}

/** @template-extends ArrayCollection<array-key, mixed> */
class AnotherCollection extends ArrayCollection
{
}
