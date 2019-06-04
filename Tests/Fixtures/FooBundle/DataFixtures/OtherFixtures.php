<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\FixtureGroupInterface;
use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class OtherFixtures implements ODMFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        // ...
    }

    public static function getGroups()
    {
        return ['staging', 'fulfilledDependencyGroup'];
    }
}
