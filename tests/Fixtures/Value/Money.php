<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Value;

final class Money
{
    public function __construct(public readonly int $amount)
    {
    }
}
