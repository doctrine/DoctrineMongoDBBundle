<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;

#[ODM\Document(collection: 'DoctrineMongoDBBundle_Tests_Validator_Document')]
class Document
{
    #[ODM\Id(strategy: 'none')]
    protected ObjectId $id;

    #[ODM\Field(type: Type::STRING)]
    public string $name;

    #[ODM\Field(type: Type::HASH)]
    public array $hash;

    #[ODM\Field(type: Type::COLLECTION)]
    public array $collection;

    #[ODM\ReferenceOne(targetDocument: self::class)]
    public ?Document $referenceOne = null;

    #[ODM\EmbedOne(targetDocument: EmbeddedDocument::class)]
    public ?EmbeddedDocument $embedOne = null;

    /** @var EmbeddedDocument[] */
    #[ODM\EmbedMany(targetDocument: EmbeddedDocument::class)]
    public array $embedMany = [];

    public function __construct(ObjectId $id)
    {
        $this->id = $id;
    }
}

#[ODM\EmbeddedDocument]
class EmbeddedDocument
{
    #[ODM\Field(type: Type::STRING)]
    public string $name;
}
