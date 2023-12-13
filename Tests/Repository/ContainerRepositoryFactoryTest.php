<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Repository;

use Doctrine\Bundle\MongoDBBundle\Repository\ContainerRepositoryFactory;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepositoryInterface;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

use function sprintf;
use function sys_get_temp_dir;

class ContainerRepositoryFactoryTest extends TestCase
{
    public function testGetRepositoryReturnsService(): void
    {
        $dm        = $this->createDocumentManager([CoolDocument::class => 'my_repo']);
        $repo      = new StubRepository($dm, $dm->getUnitOfWork(), new ClassMetadata(CoolDocument::class));
        $container = $this->createContainer(['my_repo' => $repo]);

        $factory = new ContainerRepositoryFactory($container);
        $this->assertSame($repo, $factory->getRepository($dm, CoolDocument::class));
    }

    public function testGetRepositoryReturnsDocumentRepository(): void
    {
        $container = $this->createContainer([]);
        $dm        = $this->createDocumentManager([BoringDocument::class => null]);

        $factory    = new ContainerRepositoryFactory($container);
        $actualRepo = $factory->getRepository($dm, BoringDocument::class);
        $this->assertInstanceOf(DocumentRepository::class, $actualRepo);
        // test the same instance is returned
        $this->assertSame($actualRepo, $factory->getRepository($dm, BoringDocument::class));
    }

    public function testCustomRepositoryIsReturned(): void
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

    public function testServiceRepositoriesMustExtendDocumentRepository(): void
    {
        $repo = new stdClass();

        $container = $this->createContainer(['my_repo' => $repo]);

        $dm = $this->createDocumentManager([CoolDocument::class => 'my_repo']);

        $factory = new ContainerRepositoryFactory($container);

        $this->expectExceptionMessage(
            'The service "my_repo" must extend DocumentRepository (or a base class, like ServiceDocumentRepository).',
        );
        $this->expectException(RuntimeException::class);
        $factory->getRepository($dm, CoolDocument::class);
    }

    public function testRepositoryMatchesServiceInterfaceButServiceNotFound(): void
    {
        $container = $this->createContainer([]);

        $dm = $this->createDocumentManager([
            CoolDocument::class => StubServiceRepository::class,
        ]);

        $factory = new ContainerRepositoryFactory($container);

        $this->expectExceptionMessage(sprintf(
            'The "%s" document repository implements "%s", but its service could not be found.'
            . ' Make sure the service exists and is tagged with "doctrine_mongodb.odm.repository_service".',
            StubServiceRepository::class,
            ServiceDocumentRepositoryInterface::class,
        ));
        $this->expectException(RuntimeException::class);

        $factory->getRepository($dm, CoolDocument::class);
    }

    public function testCustomRepositoryIsNotAValidClass(): void
    {
        $container = $this->createContainer([]);

        $dm = $this->createDocumentManager([CoolDocument::class => 'not_a_real_class']);

        $factory = new ContainerRepositoryFactory($container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" document has a repositoryClass set to "not_a_real_class", but this is not a valid class.'
            . ' Check your class naming. If this is meant to be a service id, make sure this service exists and'
            . ' is tagged with "doctrine_mongodb.odm.repository_service".',
            CoolDocument::class,
        ));
        $factory->getRepository($dm, CoolDocument::class);
    }

    private function createContainer(array $services): MockObject|ContainerInterface
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container
            ->method('has')
            ->willReturnCallback(static fn ($id) => isset($services[$id]));
        $container
            ->method('get')
            ->willReturnCallback(static fn ($id) => $services[$id]);

        return $container;
    }

    private function createDocumentManager(array $documentRepositoryClasses): MockObject|DocumentManager
    {
        $classMetadatas = [];
        foreach ($documentRepositoryClasses as $documentClass => $documentRepositoryClass) {
            $metadata                            = new ClassMetadata($documentClass);
            $metadata->customRepositoryClassName = $documentRepositoryClass;

            $classMetadatas[$documentClass] = $metadata;
        }

        $dm = $this->getMockBuilder(DocumentManager::class)->disableOriginalConstructor()->getMock();
        $dm
            ->method('getClassMetadata')
            ->willReturnCallback(static fn ($class) => $classMetadatas[$class]);

        $evm = $this->createMock(EventManager::class);

        $uow = new UnitOfWork($dm, $evm, new HydratorFactory($dm, $evm, sys_get_temp_dir(), sys_get_temp_dir(), Configuration::AUTOGENERATE_EVAL));
        $dm
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $dm
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        return $dm;
    }
}

/** @template-extends DocumentRepository<object> */
class StubRepository extends DocumentRepository
{
}

/** @template-extends DocumentRepository<object> */
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
