<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Vladimir Chub <v@chub.com.ua>
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
     *     inversedBy="documents"
     * )
     */
    public $categories;

    /** @ODM\Field(type="bool") */
    public $boolField;

    /** @ODM\Field(type="boolean") */
    public $booleanField;

    /** @ODM\Field(type="float") */
    public $floatField;

    /** @ODM\Field(type="int") */
    public $intField;

    /** @ODM\Field(type="integer") */
    public $integerField;

    /** @ODM\Field(type="collection") */
    public $collectionField;
}
