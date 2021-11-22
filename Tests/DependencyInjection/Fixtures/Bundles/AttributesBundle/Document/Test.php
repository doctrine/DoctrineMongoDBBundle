<?php

declare(strict_types=1);

namespace DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\AttributesBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Test
{
}
