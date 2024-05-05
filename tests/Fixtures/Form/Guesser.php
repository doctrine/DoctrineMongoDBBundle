<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;

#[ODM\Document]
class Guesser
{
    #[ODM\Id(strategy: 'none')]
    protected ?ObjectId $id = null;

    #[ODM\Field]
    public ?string $name = null;

    #[ODM\Field(type: Type::DATE)]
    public ?DateTime $date = null;

    #[ODM\Field(type: Type::TIMESTAMP)]
    public DateTime $ts;

    /** @var Collection<int, Category> */
    #[ODM\ReferenceMany(
        targetDocument: Category::class,
        inversedBy: 'documents',
        strategy: ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY,
    )]
    public Collection $categories;

    #[ODM\Field(type: Type::BOOL)]
    public ?bool $boolField = null;

    #[ODM\Field(type: Type::FLOAT)]
    public ?float $floatField = null;

    #[ODM\Field(type: Type::INT)]
    public ?int $intField = null;

    #[ODM\Field(type: Type::COLLECTION)]
    public array $collectionField;

    public mixed $nonMappedField;
}
