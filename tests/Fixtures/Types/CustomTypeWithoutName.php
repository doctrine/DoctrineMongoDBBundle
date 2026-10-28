<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types;

use Doctrine\Bundle\MongoDBBundle\Attribute\AsFieldType;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;

#[AsFieldType]
final class CustomTypeWithoutName extends Type
{
    use ClosureToPHP;
}
