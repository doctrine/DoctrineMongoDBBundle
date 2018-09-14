<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Repository;

use Doctrine\Bundle\MongoDBBundle\Repository\ContainerRepositoryFactory;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepositoryInterface;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerRepositoryFactoryTest extends TestCase
{
    public function testGetRepositoryReturnsService()
    {
        $dm        = $this->createDocumentManager([CoolDocument::class => 'my_repo']);
        $repo      = new StubRepository($dm, $dm->getUnitOfWork(), new ClassMetadata(CoolDocument::class));
        $container = $this->createContainer(['my_repo' => $repo]);

        $factory = new ContainerRepositoryFactory($container);
        $this->assertSame($repo, $factory->getRepository($dm, CoolDocument::class));
    }

    public function testGetRepositoryReturnsDocumentRepository()
    {
        $container = $this->createContainer([]);
        $dm        = $this->createDocumentManager([BoringDocument::class => null]);

        $factory    = new ContainerRepositoryFactory($container);
        $actualRepo = $factory->getRepository($dm, BoringDocument::class);
        $this->assertInstanceOf(DocumentRepository::class, $actualRepo);
        // test the same instance is returned
        $this->assertSame($actualRepo, $factory->getRepository($dm, BoringDocument::class));
    }

    public function testCustomRepositoryIsReturned()
    {
        $container = $this->createContainer([]);
        $dm        = $this->createDocumentManager([
            CustomNormalRepoDocument::class => StubRepository::class,
        ]);

        $factory    = new ContainerRepositoryFactory($container);
        $actualRepo = $factory->getRepository($dm, CustomNormalRepoDocument::class);
        $this->assertInstanceOf(StubRepository::class, $actualRepo);
        // test the same instance is returned
        $this->assertSame($actualRepo, $factory->getRepository($dm, CustomNormalRepoDocument::class));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The service "my_repo" must extend DocumentRepository (or a base class, like ServiceDocumentRepository).
     */
    public function testServiceRepositoriesMustExtendDocumentRepository()
    {
        $repo = new \stdClass();

        $container = $this->createContainer(['my_repo' => $repo]);

        $dm = $this->createDocumentManager([CoolDocument::class => 'my_repo']);

        $factory = new ContainerRepositoryFactory($container);
        $factory->getRepository($dm, CoolDocument::class);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Doctrine\Bundle\MongoDBBundle\Tests\Repository\StubServiceRepository" document repository implements "Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepositoryInterface", but its service could not be found. Make sure the service exists and is tagged with "doctrine_mongodb.odm.repository_service".
     */
    public function testRepositoryMatchesServiceInterfaceButServiceNotFound()
    {
        $container = $this->createContainer([]);

        $dm = $this->createDocumentManager([
            CoolDocument::class => StubServiceRepository::class,
        ]);

        $factory = new ContainerRepositoryFactory($container);
        $factory->getRepository($dm, CoolDocument::class);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Doctrine\Bundle\MongoDBBundle\Tests\Repository\CoolDocument" document has a repositoryClass set to "not_a_real_class", but this is not a valid class. Check your class naming. If this is meant to be a service id, make sure this service exists and is tagged with "doctrine_mongodb.odm.repository_service".
     */
    public function testCustomRepositoryIsNotAValidClass()
    {
        $container = $this->createContainer([]);

        $dm = $this->createDocumentManager([CoolDocument::class => 'not_a_real_class']);

        $factory = new ContainerRepositoryFactory($container);
        $factory->getRepository($dm, CoolDocument::class);
    }

    /**
     * @return MockObject|ContainerInterface
     */
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

    /**
     * @return MockObject|DocumentManager
     */
    private function createDocumentManager(array $documentRepositoryClasses)
    {
        $classMetadatas = [];
        foreach ($documentRepositoryClasses as $documentClass => $documentRepositoryClass) {
            $metadata                            = new ClassMetadata($documentClass);
            $metadata->customRepositoryClassName = $documentRepositoryClass;

            $classMetadatas[$documentClass] = $metadata;
        }

        $dm = $this->getMockBuilder(DocumentManager::class)->disableOriginalConstructor()->getMock();
        $dm->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnCallback(function ($class) use ($classMetadatas) {
                return $classMetadatas[$class];
            });

        $uow = new UnitOfWork($dm, $this->createMock(EventManager::class), $this->createMock(HydratorFactory::class));
        $dm->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);


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

class CoolDocument
{
}

class BoringDocument
{
}

class CustomNormalRepoDocument
{
}
