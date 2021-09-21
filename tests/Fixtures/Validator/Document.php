<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document(collection="DoctrineMongoDBBundle_Tests_Validator_Document") */
class Document
{
    /**
     * @ODM\Id(strategy="none")
     *
     * @var ObjectId
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\Field(type="hash")
     *
     * @var array
     */
    public $hash;

    /**
     * @ODM\Field(type="collection")
     *
     * @var array
     */
    public $collection;

    /**
     * @ODM\ReferenceOne(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\Document")
     *
     * @var Document|null
     */
    public $referenceOne;

    /**
     * @ODM\EmbedOne(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument")
     *
     * @var EmbeddedDocument|null
     */
    public $embedOne;

    /**
     * @ODM\EmbedMany(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument")
     *
     * @var EmbeddedDocument[]
     */
    public $embedMany = [];

    public function __construct(ObjectId $id)
    {
        $this->id = $id;
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;
}
