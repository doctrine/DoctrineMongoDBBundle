<?php

namespace Doctrine\Bundle\MongoDBBundle\Loader;

use Doctrine\Common\DataFixtures\FixtureInterface;

interface SymfonyFixturesLoaderInterface
{
    /**
     * Add multple fixtures
     *
     * @internal
     * @param array $fixtures
     */
    public function addFixtures($fixtures);

    /**
     * Add a single fixture
     *
     * @param FixtureInterface $fixture
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
    public function getFixtures($groups = []);
}
