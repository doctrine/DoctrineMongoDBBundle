<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Fixture;

use Doctrine\Common\DataFixtures\FixtureInterface;

/**
 * Marks your fixtures that are specifically for the ODM.
 */
interface ODMFixtureInterface extends FixtureInterface
{
}
