<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Guesser
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\Field() */
    public $name;

    /** @ODM\Field(type="date") */
    public $date;

    /** @ODM\Field(type="timestamp") */
    public $ts;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category",
     *     inversedBy="documents",
     *     strategy="atomicSetArray"
     * )
     */
    public $categories;

    /** @ODM\Field(type="bool") */
    public $boolField;

    /** @ODM\Field(type="float") */
    public $floatField;

    /** @ODM\Field(type="int") */
    public $intField;

    /** @ODM\Field(type="collection") */
    public $collectionField;

    public $nonMappedField;
}
