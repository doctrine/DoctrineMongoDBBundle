<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\FixtureGroupInterface;
use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures implements ODMFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
    }

    public static function getGroups(): array
    {
        return ['test_group'];
    }
}
