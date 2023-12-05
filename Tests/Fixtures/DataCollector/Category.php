<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\DataCollector;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;

#[ODM\Document]
class Category
{
    #[ODM\Id]
    protected ?ObjectId $id = null;

    #[ODM\Field(type: Type::STRING)]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
