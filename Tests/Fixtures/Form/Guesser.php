<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Guesser
{
    /** @ODM\Id(strategy="none") */
    protected ?ObjectId $id = null;

    /** @ODM\Field() */
    public ?string $name = null;

    /** @ODM\Field(type="date") */
    public ?DateTime $date = null;

    /** @ODM\Field(type="timestamp") */
    public DateTime $ts;

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

    /** @ODM\Field(type="bool") */
    public ?bool $boolField = null;

    /** @ODM\Field(type="float") */
    public ?float $floatField = null;

    /** @ODM\Field(type="int") */
    public ?int $intField = null;

    /**
     * @ODM\Field(type="collection")
     *
     * @var array
     */
    public array $collectionField;

    public mixed $nonMappedField;
}
