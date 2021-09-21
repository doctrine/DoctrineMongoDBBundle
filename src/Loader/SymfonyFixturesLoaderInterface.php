<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Loader;

use Doctrine\Common\DataFixtures\FixtureInterface;

interface SymfonyFixturesLoaderInterface
{
    /**
     * Add multple fixtures
     *
     * @internal
     *
     * @param array $fixtures
     */
    public function addFixtures(array $fixtures);

    /**
     * Add a single fixture
     *
     * @return mixed
     */
    public function addFixture(FixtureInterface $fixture);

    /**
     * Returns the array of data fixtures to execute.
     *
     * @param string[] $groups
     *
     * @return FixtureInterface[]
     */
    public function getFixtures(array $groups = []);
}
