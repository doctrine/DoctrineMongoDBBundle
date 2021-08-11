<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/**
 * @ODM\Document
 */
class Guesser
{
    /**
     * @ODM\Id(strategy="none")
     *
     * @var ObjectId|null
     */
    protected $id;

    /**
     * @ODM\Field()
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    public $date;

    /**
     * @ODM\Field(type="timestamp")
     *
     * @var DateTime
     */
    public $ts;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category",
     *     inversedBy="documents",
     *     strategy="atomicSetArray"
     * )
     *
     * @var Collection<int, Category>
     */
    public $categories;

    /**
     * @ODM\Field(type="bool")
     *
     * @var bool|null
     */
    public $boolField;

    /**
     * @ODM\Field(type="float")
     *
     * @var float|null
     */
    public $floatField;

    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $intField;

    /**
     * @ODM\Field(type="collection")
     *
     * @var array
     */
    public $collectionField;

    /** @var mixed */
    public $nonMappedField;
}
