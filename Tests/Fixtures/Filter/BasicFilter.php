<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

final class BasicFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $class): array
    {
        return [];
    }
}
