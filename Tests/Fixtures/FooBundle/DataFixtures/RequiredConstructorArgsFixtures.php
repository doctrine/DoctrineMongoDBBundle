<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class RequiredConstructorArgsFixtures implements ODMFixtureInterface
{
    public function __construct($fooRequiredArg)
    {
    }

    public function load(ObjectManager $manager) : void
    {
        // ...
    }
}
