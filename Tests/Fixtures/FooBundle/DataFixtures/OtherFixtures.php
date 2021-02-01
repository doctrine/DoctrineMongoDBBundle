<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\FixtureGroupInterface;
use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OtherFixtures implements ODMFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // ...
    }

    public static function getGroups(): array
    {
        return ['staging', 'fulfilledDependencyGroup'];
    }
}
