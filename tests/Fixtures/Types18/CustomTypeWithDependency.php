<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types18;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * The constructor requires doctrine/mongodb-odm 2.18, where Type no longer declares
 * a final private constructor.
 */
final class CustomTypeWithDependency extends Type
{
    use ClosureToPHP;

    public function __construct(public readonly string $dependency)
    {
    }
}
