<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types;

use Doctrine\Bundle\MongoDBBundle\Attribute\AsFieldType;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Value\Money;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;

#[AsFieldType(Money::class)]
final class MoneyType extends Type
{
    use ClosureToPHP;

    public function convertToPHPValue(mixed $value): ?Money
    {
        return $value === null ? null : new Money((int) $value);
    }

    public function convertToDatabaseValue(mixed $value): ?int
    {
        return $value instanceof Money ? $value->amount : null;
    }
}
