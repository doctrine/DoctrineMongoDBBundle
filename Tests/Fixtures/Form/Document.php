<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Document
{
    /** @ODM\Id(strategy="none") */
    protected ObjectId $id;

    /** @ODM\Field(type="string") */
    public string $name;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category",
     *     inversedBy="documents",
     *     strategy="atomicSetArray"
     * )
     *
     * @var Collection<int, Category>
     */
    public Collection $categories;

    public function __construct(ObjectId $id, string $name)
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->categories = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
