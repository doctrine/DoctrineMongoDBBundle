<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;

#[ODM\Document]
class Document
{
    #[ODM\Id(strategy: 'none')]
    protected ObjectId $id;

    #[ODM\Field(type: Type::STRING)]
    public string $name;

    /** @var Collection<int, Category> */
    #[ODM\ReferenceMany(
        targetDocument: Category::class,
        inversedBy: 'documents',
        strategy: ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY,
    )]
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
