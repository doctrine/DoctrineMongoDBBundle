<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="DoctrineMongoDBBundle_Tests_Validator_Document") */
class Document
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="hash") */
    public $hash;

    /** @ODM\Collection */
    public $collection;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\Document") */
    public $referenceOne;

    /** @ODM\EmbedOne(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument") */
    public $embedMany = [];

    public function __construct($id) {
        $this->id = $id;
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;
}
