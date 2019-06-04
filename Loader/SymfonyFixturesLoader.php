<?php

namespace Doctrine\Bundle\MongoDBBundle\Loader;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\FixturesCompilerPass;
use Doctrine\Bundle\MongoDBBundle\Fixture\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use function array_key_exists;
use function array_values;
use function get_class;
use function sprintf;

final class SymfonyFixturesLoader extends ContainerAwareLoader implements SymfonyFixturesLoaderInterface
{
    /** @var FixtureInterface[] */
    private $loadedFixtures = [];

    /** @var array<string, array<string, bool>> */
    private $groupsFixtureMapping = [];

    /**
     * @internal
     * @param array $fixtures
     */
    public function addFixtures($fixtures)
    {
        // Because parent::addFixture may call $this->createFixture
        // we cannot call $this->addFixture in this loop
        foreach ($fixtures as $fixture) {
            $class                        = get_class($fixture['fixture']);
            $this->loadedFixtures[$class] = $fixture['fixture'];
            $this->addGroupsFixtureMapping($class, $fixture['groups']);
        }

        // Now that all fixtures are in the $this->loadedFixtures array,
        // it is safe to call $this->addFixture in this loop
        foreach ($this->loadedFixtures as $fixture) {
            $this->addFixture($fixture);
        }
    }

    public function addFixture(FixtureInterface $fixture)
    {
        $class                        = get_class($fixture);
        $this->loadedFixtures[$class] = $fixture;

        // see https://github.com/doctrine/data-fixtures/pull/274
        // this is to give a clear error if you do not have this version
        if (!method_exists(Loader::class, 'createFixture')) {
            $this->checkForNonInstantiableFixtures($fixture);
        }

        $reflection = new ReflectionClass($fixture);
        $this->addGroupsFixtureMapping($class, [$reflection->getShortName()]);

        if ($fixture instanceof FixtureGroupInterface) {
            $this->addGroupsFixtureMapping($class, $fixture::getGroups());
        }

        parent::addFixture($fixture);
    }

    /**
     * Overridden to not allow new fixture classes to be instantiated.
     *
     * @param string $class
     * @return FixtureInterface
     */
    protected function createFixture($class)
    {
        /*
         * We don't actually need to create the fixture. We just
         * return the one that already exists.
         */

        if (! isset($this->loadedFixtures[$class])) {
            throw new LogicException(sprintf(
                'The "%s" fixture class is trying to be loaded, but is not available. Make sure this class is defined as a service and tagged with "%s".',
                $class,
                FixturesCompilerPass::FIXTURE_TAG
            ));
        }

        return $this->loadedFixtures[$class];
    }

    /**
     * Returns the array of data fixtures to execute.
     *
     * @param string[] $groups
     *
     * @return FixtureInterface[]
     */
    public function getFixtures($groups = [])
    {
        $fixtures = parent::getFixtures();

        if (empty($groups)) {
            return $fixtures;
        }

        $filteredFixtures = [];
        foreach ($fixtures as $fixture) {
            foreach ($groups as $group) {
                $fixtureClass = get_class($fixture);
                if (isset($this->groupsFixtureMapping[$group][$fixtureClass])) {
                    $filteredFixtures[$fixtureClass] = $fixture;
                    continue 2;
                }
            }
        }

        foreach ($filteredFixtures as $fixture) {
            $this->validateDependencies($filteredFixtures, $fixture);
        }

        return array_values($filteredFixtures);
    }

    /**
     * Generates an array of the groups and their fixtures
     *
     * @param $className
     * @param string[] $groups
     */
    private function addGroupsFixtureMapping($className, array $groups)
    {
        foreach ($groups as $group) {
            $this->groupsFixtureMapping[$group][$className] = true;
        }
    }

    /**
     * @param string[] $fixtures An array of fixtures with class names as keys
     * @param FixtureInterface $fixture
     */
    private function validateDependencies(array $fixtures, FixtureInterface $fixture)
    {
        if (! $fixture instanceof DependentFixtureInterface) {
            return;
        }

        $dependenciesClasses = $fixture->getDependencies();

        foreach ($dependenciesClasses as $class) {
            if (! array_key_exists($class, $fixtures)) {
                throw new RuntimeException(sprintf('Fixture "%s" was declared as a dependency for fixture "%s", but it was not included in any of the loaded fixture groups.', $class, get_class($fixture)));
            }
        }
    }

    /**
     * For doctrine/data-fixtures 1.2 or lower, this detects an unsupported
     * feature with DependentFixtureInterface so that we can throw a
     * clear exception.
     *
     * @param FixtureInterface $fixture
     * @throws \Exception
     */
    private function checkForNonInstantiableFixtures(FixtureInterface $fixture)
    {
        if (!$fixture instanceof DependentFixtureInterface) {
            return;
        }

        foreach ($fixture->getDependencies() as $dependency) {
            if (!class_exists($dependency)) {
                continue;
            }

            if (!method_exists($dependency, '__construct')) {
                continue;
            }

            $reflMethod = new \ReflectionMethod($dependency, '__construct');
            foreach ($reflMethod->getParameters() as $param) {
                if (!$param->isOptional()) {
                    throw new \LogicException(sprintf('The getDependencies() method returned a class (%s) that has required constructor arguments. Upgrade to "doctrine/data-fixtures" version 1.3 or higher to support this.', $dependency));
                }
            }
        }
    }
}
