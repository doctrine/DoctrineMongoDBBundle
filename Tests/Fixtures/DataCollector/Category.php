<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\DataCollector;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Category
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
