<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DependentOnRequiredConstructorArgsFixtures implements ODMFixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // ...
    }

    public function getDependencies(): array
    {
        return [RequiredConstructorArgsFixtures::class];
    }
}
