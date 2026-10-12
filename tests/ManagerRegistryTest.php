<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

use function in_array;

class ManagerRegistryTest extends TestCase
{
    public function testReset(): void
    {
        $container = new ContainerBuilder();
        $container->register('manager.default', DocumentManagerStub::class)
            ->setPublic(true);
        $container->register('manager.lazy', DocumentManagerStub::class)
            ->setPublic(true)
            ->setLazy(true)
            ->addTag('proxy', ['interface' => ObjectManager::class]);
        $container->compile();

        /** @var class-string<Container> $containerClass */
        $containerClass = 'MongoDBManagerRepositoryTestResetContainer';
        $dumper         = new PhpDumper($container);
        eval('?' . '>' . $dumper->dump(['class' => $containerClass]));

        $container  = new $containerClass();
        $repository = new ManagerRegistry('MongoDB', [], [
            'default' => 'manager.default',
            'lazy' => 'manager.lazy',
        ], '', '', '', $container);

        DocumentManagerStub::$clearCount = 0;

        $repository->reset();

        // Service was not initialized, so reset should not be called
        $this->assertSame(0, DocumentManagerStub::$clearCount);

        // The lazy service is reinitialized instead of being cleared
        $container->get('manager.lazy')->flush();
        $repository->reset();
        // The counter is incremented by clear(), which is called through the container,
        // so static analysis narrows it to the 0 assigned earlier and sees this
        // assertion as always true.
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame(0, DocumentManagerStub::$clearCount);

        // The default service is cleared when initialized
        $container->get('manager.default')->flush();
        $repository->reset();
        $this->assertSame(1, DocumentManagerStub::$clearCount);
    }

    public function testGetManagerForClassDoesNotInitializeManagersThatDoNotMapTheClass(): void
    {
        $container = new ContainerBuilder();
        $container->register('manager.default', DocumentManagerStub::class)->setPublic(true);
        $container->register('manager.other', DocumentManagerStub::class)->setPublic(true);
        $container->compile();

        $repository = new ManagerRegistry('MongoDB', [], [
            'default' => 'manager.default',
            'other' => 'manager.other',
        ], '', '', Proxy::class, $container, new MetadataDriverLocator([
            'default' => new MappingDriverStub([DocumentManagerStub::class]),
            'other' => new MappingDriverStub([self::class]),
        ]));

        // No manager maps stdClass, so none of them is initialized
        $this->assertNull($repository->getManagerForClass(stdClass::class));
        $this->assertFalse($container->initialized('manager.default'));
        $this->assertFalse($container->initialized('manager.other'));

        // Only the manager that maps the class is initialized
        $manager = $repository->getManagerForClass(DocumentManagerStub::class);
        $this->assertSame($container->get('manager.default'), $manager);
        $this->assertFalse($container->initialized('manager.other'));
    }
}

class MetadataDriverLocator implements ContainerInterface
{
    /** @param array<string, MappingDriver> $drivers */
    public function __construct(private readonly array $drivers)
    {
    }

    public function get(string $id): MappingDriver
    {
        return $this->drivers[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->drivers[$id]);
    }
}

class MappingDriverStub implements MappingDriver
{
    /** @param list<class-string> $mappedClasses */
    public function __construct(private readonly array $mappedClasses)
    {
    }

    public function loadMetadataForClass(string $className, ClassMetadata $metadata): void
    {
    }

    /** @return list<class-string> */
    public function getAllClassNames(): array
    {
        return $this->mappedClasses;
    }

    public function isTransient(string $className): bool
    {
        return ! in_array($className, $this->mappedClasses, true);
    }
}

class DocumentManagerStub extends DocumentManager
{
    public static int $clearCount;

    public function __construct()
    {
    }

    /** {@inheritDoc} */
    public function clear($objectName = null): void
    {
        self::$clearCount++;
    }

    public function flush(array $options = []): void
    {
    }
}
