<?php

declare(strict_types = 1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use function func_get_args;
use Memcached;

final class MemcachedStub extends Memcached
{
    public function addServer($host, $port, $weight = 0)
    {
        parent::addServer(...func_get_args());
    }
}
