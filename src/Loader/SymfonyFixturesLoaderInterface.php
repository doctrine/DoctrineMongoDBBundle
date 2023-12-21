<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Loader;

use Doctrine\Common\DataFixtures\FixtureInterface;

interface SymfonyFixturesLoaderInterface
{
    /**
     * Add multiple fixtures
     *
     * @internal
     *
     * @param list<array{fixture: FixtureInterface, groups: string[]}> $fixtures
     */
    public function addFixtures(array $fixtures): void;

    /**
     * Add a single fixture
     */
    public function addFixture(FixtureInterface $fixture): void;

    /**
     * Returns the array of data fixtures to execute.
     *
     * @param string[] $groups
     *
     * @return FixtureInterface[]
     */
    public function getFixtures(array $groups = []): array;
}
