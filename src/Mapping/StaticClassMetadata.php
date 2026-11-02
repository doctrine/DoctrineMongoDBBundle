<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

interface StaticClassMetadata
{
    public static function loadMetadata(ClassMetadata $metadata): void;
}
