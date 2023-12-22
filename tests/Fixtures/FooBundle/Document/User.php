<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document(collection: 'doctrine_mongodb_test_user')]
class User
{
    public function __construct(
        #[Id(strategy: 'NONE')]
        private string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
