<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Category
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|string|null
     */
    protected $id;

    /** @ODM\Field(type="string") */
    public string $name;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document",
     *     mappedBy="categories"
     * )
     *
     * @var Collection<int, Document>
     */
    public Collection $documents;

    public function __construct(string $name)
    {
        $this->name      = $name;
        $this->documents = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
