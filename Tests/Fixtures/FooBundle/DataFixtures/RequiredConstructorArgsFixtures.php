<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RequiredConstructorArgsFixtures implements ODMFixtureInterface
{
    /** @param mixed $fooRequiredArg */
    public function __construct($fooRequiredArg)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // ...
    }
}
