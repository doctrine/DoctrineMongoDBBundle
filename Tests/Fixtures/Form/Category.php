<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
#[ODM\Document]
class Category
{
    /** @ODM\Id */
    #[ODM\Id]
    protected ObjectId|string|null $id;

    /** @ODM\Field(type="string") */
    #[ODM\Field(type: Type::STRING)]
    public string $name;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document",
     *     mappedBy="categories"
     * )
     *
     * @var Collection<int, Document>
     */
    #[ODM\ReferenceMany(
        targetDocument: Document::class,
        mappedBy: 'categories',
    )]
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
