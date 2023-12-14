<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Fixture;

/**
 * FixtureGroupInterface can be implemented by fixtures that belong in groups
 */
interface FixtureGroupInterface
{
    /**
     * This method must return an array of groups
     * on which the implementing class belongs to
     *
     * @return string[]
     */
    public static function getGroups(): array;
}
