<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\FixturesCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\DependentOnRequiredConstructorArgsFixtures;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\OtherFixtures;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\RequiredConstructorArgsFixtures;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\WithDependenciesFixtures;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\FooBundle;
use Doctrine\Common\DataFixtures\Loader;
use RuntimeException;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use function array_map;
use function class_exists;
use function get_class;
use function method_exists;
use function rand;
use function sys_get_temp_dir;

class FixtureIntegrationTest extends TestCase
{
    protected function setUp()
    {
        if (class_exists(Loader::class)) {
            return;
        }

        $this->markTestSkipped();
    }

    public function testFixturesLoader() : void
    {
        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $actualFixtures = $loader->getFixtures();
        $this->assertCount(2, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
            WithDependenciesFixtures::class,
        ], $actualFixtureClasses);
        $this->assertInstanceOf(WithDependenciesFixtures::class, $actualFixtures[1]);
    }

    public function testFixturesLoaderWhenFixtureHasDependencyThatIsNotYetLoaded() : void
    {
        // See https://github.com/doctrine/DoctrineFixturesBundle/issues/215

        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $actualFixtures = $loader->getFixtures();
        $this->assertCount(2, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
            WithDependenciesFixtures::class,
        ], $actualFixtureClasses);
        $this->assertInstanceOf(WithDependenciesFixtures::class, $actualFixtures[1]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The getDependencies() method returned a class (Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\RequiredConstructorArgsFixtures) that has required constructor arguments. Upgrade to "doctrine/data-fixtures" version 1.3 or higher to support this.
     */
    public function testExceptionWithDependenciesWithRequiredArguments() : void
    {
        // see https://github.com/doctrine/data-fixtures/pull/274
        // When that is merged, this test will only run when using
        // an older version of that library.
        if (method_exists(Loader::class, 'createFixture')) {
            $this->markTestSkipped();
        }

        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) {
            $c->autowire(DependentOnRequiredConstructorArgsFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->autowire(RequiredConstructorArgsFixtures::class)
                ->setArgument(0, 'foo')
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $loader->getFixtures();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The "Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\RequiredConstructorArgsFixtures" fixture class is trying to be loaded, but is not available. Make sure this class is defined as a service and tagged with "doctrine.fixture.odm.mongodb".
     */
    public function testExceptionIfDependentFixtureNotWired() : void
    {
        // only runs on newer versions of doctrine/data-fixtures
        if (! method_exists(Loader::class, 'createFixture')) {
            $this->markTestSkipped();
        }

        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(DependentOnRequiredConstructorArgsFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $loader->getFixtures();
    }

    public function testFixturesLoaderWithGroupsOptionViaInterface() : void
    {
        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $actualFixtures = $loader->getFixtures(['staging']);
        $this->assertCount(1, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
        ], $actualFixtureClasses);
        $this->assertInstanceOf(OtherFixtures::class, $actualFixtures[0]);
    }

    public function testFixturesLoaderWithGroupsOptionViaTag() : void
    {
        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'group1'])
                ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'group2']);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'group2']);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $this->assertCount(1, $loader->getFixtures(['staging']));
        $this->assertCount(1, $loader->getFixtures(['group1']));
        $this->assertCount(2, $loader->getFixtures(['group2']));
        $this->assertCount(0, $loader->getFixtures(['group3']));
    }

    public function testLoadFixturesViaGroupWithMissingDependency() : void
    {
        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fixture "Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\OtherFixtures" was declared as a dependency for fixture "Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\DataFixtures\WithDependenciesFixtures", but it was not included in any of the loaded fixture groups.');

        $loader->getFixtures(['missingDependencyGroup']);
    }

    public function testLoadFixturesViaGroupWithFulfilledDependency() : void
    {
        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $actualFixtures = $loader->getFixtures(['fulfilledDependencyGroup']);

        $this->assertCount(2, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
            WithDependenciesFixtures::class,
        ], $actualFixtureClasses);
    }

    public function testLoadFixturesByShortName() : void
    {
        $kernel = new IntegrationTestKernel('dev', false);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine_mongodb.odm.symfony.fixtures.loader', new Alias('doctrine_mongodb.odm.symfony.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine_mongodb.odm.symfony.fixtures.loader');

        $actualFixtures = $loader->getFixtures(['OtherFixtures']);

        $this->assertCount(1, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
        ], $actualFixtureClasses);
    }
}

class IntegrationTestKernel extends Kernel
{
    use MicroKernelTrait;

    /** @var callable */
    private $servicesCallback;

    /** @var int */
    private $randomKey;

    public function __construct(string $environment, bool $debug)
    {
        $this->randomKey = rand(100, 999);

        parent::__construct($environment, $debug);
    }

    protected function getContainerClass() : string
    {
        return 'test' . $this->randomKey . parent::getContainerClass();
    }

    public function registerBundles() : array
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle(),
            new FooBundle(),
        ];
    }

    protected function build(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('doctrine_mongodb', [
            'connections' => ['default' => []],
            'document_managers' => ['default' => []],
        ]);
    }

    public function addServices(callable $callback) : void
    {
        $this->servicesCallback = $callback;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes) : void
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader) : void
    {
        $c->setParameter('kernel.secret', 'foo');

        $callback = $this->servicesCallback;
        $callback($c);
    }

    public function getCacheDir() : string
    {
        return sys_get_temp_dir() . '/doctrine_mongodb_odm_bundle' . $this->randomKey;
    }

    public function getLogDir() : string
    {
        return sys_get_temp_dir();
    }
}
