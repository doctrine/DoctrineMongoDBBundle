<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

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
        $this->assertSame(0, DocumentManagerStub::$clearCount);

        // The default service is cleared when initialized
        $container->get('manager.default')->flush();
        $repository->reset();
        $this->assertSame(1, DocumentManagerStub::$clearCount);
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
