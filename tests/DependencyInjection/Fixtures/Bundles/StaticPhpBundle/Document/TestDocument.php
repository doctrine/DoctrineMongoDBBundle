<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\AttributesBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Mapping\StaticClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

class TestDocument implements StaticClassMetadata
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField([
            'name'      => '_id',
            'fieldName' => 'id',
            'type'      => Type::ID,
            'id'        => true,
        ]);
        $metadata->mapField([
            'name'      => 'name',
            'fieldName' => 'name',
            'type'      => Type::STRING,
        ]);
    }
}
