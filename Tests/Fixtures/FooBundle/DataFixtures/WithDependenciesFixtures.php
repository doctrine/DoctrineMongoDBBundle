<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\FixtureGroupInterface;
use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class WithDependenciesFixtures implements ODMFixtureInterface, DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        // ...
    }

    public function getDependencies()
    {
        return [OtherFixtures::class];
    }

    public static function getGroups()
    {
        return ['missingDependencyGroup', 'fulfilledDependencyGroup'];
    }
}
