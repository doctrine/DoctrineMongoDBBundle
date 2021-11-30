<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OtherFixtures implements ODMFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
    }
}
