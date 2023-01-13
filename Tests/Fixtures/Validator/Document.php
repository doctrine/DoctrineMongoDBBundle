<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document(collection="DoctrineMongoDBBundle_Tests_Validator_Document") */
class Document
{
    /** @ODM\Id(strategy="none") */
    protected ObjectId $id;

    /** @ODM\Field(type="string") */
    public string $name;

    /**
     * @ODM\Field(type="hash")
     *
     * @var array
     */
    public array $hash;

    /**
     * @ODM\Field(type="collection")
     *
     * @var array
     */
    public array $collection;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\Document") */
    public ?Document $referenceOne = null;

    /** @ODM\EmbedOne(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument") */
    public ?EmbeddedDocument $embedOne = null;

    /**
     * @ODM\EmbedMany(targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument")
     *
     * @var EmbeddedDocument[]
     */
    public array $embedMany = [];

    public function __construct(ObjectId $id)
    {
        $this->id = $id;
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public string $name;
}
