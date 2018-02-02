<?php

namespace Doctrine\Bundle\DoctrineBundle\Tests\Repository;

use DoctrineMongoDBBundle\DoctrineMongoDBBundle\Repository\ServiceDocumentRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\Repository\ContainerRepositoryFactory;
use Doctrine\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerRepositoryFactoryTest extends TestCase
{
    public function testGetRepositoryReturnsService()
    {
        if (!interface_exists(ContainerInterface::class)) {
            $this->markTestSkipped('Symfony 3.3 is needed for this feature.');
        }

        $dm = $this->createTestDocumentManager([
            'Foo\CoolDocument' => 'my_repo',
        ]);
        $repo = new StubRepository($dm, new ClassMetadata(''));
        $container = $this->createContainer([
            'my_repo' => $repo,
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $this->assertSame($repo, $factory->getRepository($dm, 'Foo\CoolDocument'));
    }

    public function testGetRepositoryReturnsDocumentRepository()
    {
        if (!interface_exists(ContainerInterface::class)) {
            $this->markTestSkipped('Symfony 3.3 is needed for this feature.');
        }

        $container = $this->createContainer([]);
        $dm = $this->createTestDocumentManager([
            'Foo\BoringDocument' => null,
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $actualRepo = $factory->getRepository($dm, 'Foo\BoringDocument');
        $this->assertInstanceOf(DocumentRepository::class, $actualRepo);
        // test the same instance is returned
        $this->assertSame($actualRepo, $factory->getRepository($dm, 'Foo\BoringDocument'));
    }

    public function testCustomRepositoryIsReturned()
    {
        if (!interface_exists(ContainerInterface::class)) {
            $this->markTestSkipped('Symfony 3.3 is needed for this feature.');
        }

        $container = $this->createContainer([]);
        $dm = $this->createTestDocumentManager([
            'Foo\CustomNormalRepoDocument' => StubRepository::class,
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $actualRepo = $factory->getRepository($dm, 'Foo\CustomNormalRepoDocument');
        $this->assertInstanceOf(StubRepository::class, $actualRepo);
        // test the same instance is returned
        $this->assertSame($actualRepo, $factory->getRepository($dm, 'Foo\CustomNormalRepoDocument'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The service "my_repo" must extend DocumentRepository (or a base class, like ServiceDocumentRepository).
     */
    public function testServiceRepositoriesMustExtendDocumentRepository()
    {
        if (!interface_exists(ContainerInterface::class)) {
            $this->markTestSkipped('Symfony 3.3 is needed for this feature.');
        }

        $repo = new \stdClass();

        $container = $this->createContainer([
            'my_repo' => $repo,
        ]);

        $dm = $this->createTestDocumentManager([
            'Foo\CoolDocument' => 'my_repo',
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $factory->getRepository($dm, 'Foo\CoolDocument');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Doctrine\Bundle\DoctrineBundle\Tests\Repository\StubServiceRepository" entity repository implements "Doctrine\Bundle\DoctrineBundle\Repository\ServiceDocumentRepositoryInterface", but its service could not be found. Make sure the service exists and is tagged with "doctrine.repository_service".
     */
    public function testRepositoryMatchesServiceInterfaceButServiceNotFound()
    {
        if (!interface_exists(ContainerInterface::class)) {
            $this->markTestSkipped('Symfony 3.3 is needed for this feature.');
        }

        $container = $this->createContainer([]);

        $dm = $this->createTestDocumentManager([
            'Foo\CoolDocument' => StubServiceRepository::class,
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $factory->getRepository($dm, 'Foo\CoolDocument');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Foo\CoolDocument" entity has a repositoryClass set to "not_a_real_class", but this is not a valid class. Check your class naming. If this is meant to be a service id, make sure this service exists and is tagged with "doctrine.repository_service".
     */
    public function testCustomRepositoryIsNotAValidClass()
    {
        if (interface_exists(ContainerInterface::class)) {
            $container = $this->createContainer([]);
        } else {
            // Symfony 3.2 and lower support
            $container = null;
        }

        $dm = $this->createTestDocumentManager([
            'Foo\CoolDocument' => 'not_a_real_class',
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $factory->getRepository($dm, 'Foo\CoolDocument');
    }

    private function createContainer(array $services)
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->any())
            ->method('has')
            ->willReturnCallback(function ($id) use ($services) {
                return isset($services[$id]);
            });
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                return $services[$id];
            });

        return $container;
    }

    private function createTestDocumentManager(array $DocumentRepositoryClasses)
    {
        $classMetadatas = [];
        foreach ($DocumentRepositoryClasses as $entityClass => $DocumentRepositoryClass) {
            $metadata = new ClassMetadata($entityClass);
            $metadata->customRepositoryClassName = $DocumentRepositoryClass;

            $classMetadatas[$entityClass] = $metadata;
        }

        // TODO
        $dm = $this->getMockBuilder(DocumentManagerInterface::class)->getMock();
        $dm->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnCallback(function ($class) use ($classMetadatas) {
                return $classMetadatas[$class];
            });

        $dm->expects($this->any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        return $dm;
    }
}

class StubRepository extends DocumentRepository
{
}

class StubServiceRepository extends DocumentRepository implements ServiceDocumentRepositoryInterface
{
}
