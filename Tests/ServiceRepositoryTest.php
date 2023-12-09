<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomClassRepoDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoFile;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestDefaultRepoDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestDefaultRepoFile;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestUnmappedDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomClassRepoRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoGridFSRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository\TestUnmappedDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\RepositoryServiceBundle;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use LogicException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use function sprintf;
use function sys_get_temp_dir;

class ServiceRepositoryTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.name' => 'app',
            'kernel.debug' => false,
            'kernel.bundles' => ['RepositoryServiceBundle' => RepositoryServiceBundle::class],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../../../',
            'kernel.project_dir' => __DIR__ . '/../../../../',
            'kernel.container_class' => Container::class,
        ]));
        $extension       = new DoctrineMongoDBExtension();
        $this->container->registerExtension($extension);

        $extension->load([
            [
                'connections' => ['default' => []],
                'document_managers' => [
                    'default' => [
                        'mappings' => [
                            'RepositoryServiceBundle' => [
                                'type' => 'attribute',
                                'dir' => __DIR__ . '/DependencyInjection/Fixtures/Bundles/RepositoryServiceBundle/Document',
                                'prefix' => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document',
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->container);

        $def = $this->container->register(TestCustomServiceRepoDocumentRepository::class, TestCustomServiceRepoDocumentRepository::class)
            ->setPublic(false);
        // create a public alias so we can use it below for testing
        $this->container->setAlias('test_alias__' . TestCustomServiceRepoDocumentRepository::class, new Alias(TestCustomServiceRepoDocumentRepository::class, true));

        $def->setAutowired(true);
        $def->setAutoconfigured(true);

        $def = $this->container->register(TestCustomServiceRepoGridFSRepository::class, TestCustomServiceRepoGridFSRepository::class)
            ->setPublic(false);
        // create a public alias so we can use it below for testing
        $this->container->setAlias('test_alias__' . TestCustomServiceRepoDocumentRepository::class, new Alias(TestCustomServiceRepoDocumentRepository::class, true));
        $this->container->setAlias('test_alias__' . TestCustomServiceRepoGridFSRepository::class, new Alias(TestCustomServiceRepoGridFSRepository::class, true));

        $def->setAutowired(true);
        $def->setAutoconfigured(true);

        $this->container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $this->container->compile();
    }

    public function testRepositoryServiceWiring(): void
    {
        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');

        // traditional custom class repository
        $customClassRepo = $dm->getRepository(TestCustomClassRepoDocument::class);
        $this->assertInstanceOf(TestCustomClassRepoRepository::class, $customClassRepo);
        // a smoke test, trying some methods
        $this->assertSame(TestCustomClassRepoDocument::class, $customClassRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customClassRepo->createQueryBuilder());

        // generic DocumentRepository
        $genericRepository = $dm->getRepository(TestDefaultRepoDocument::class);
        $this->assertInstanceOf(DocumentRepository::class, $genericRepository);
        $this->assertSame($genericRepository, $dm->getRepository(TestDefaultRepoDocument::class));
        $genericDocumentRepository = $dm->getRepository(TestDefaultRepoDocument::class);
        $this->assertInstanceOf(DocumentRepository::class, $genericDocumentRepository);
        // a smoke test, trying one of the methods
        $this->assertSame(TestDefaultRepoDocument::class, $genericDocumentRepository->getClassName());

        // custom service repository
        $customServiceRepo = $dm->getRepository(TestCustomServiceRepoDocument::class);
        $this->assertSame($customServiceRepo, $this->container->get('test_alias__' . TestCustomServiceRepoDocumentRepository::class));
        // generic GridFSRepository
        $genericGridFSRepository = $dm->getRepository(TestDefaultRepoFile::class);
        $this->assertInstanceOf(DefaultGridFSRepository::class, $genericGridFSRepository);
        // a smoke test, trying one of the methods
        $this->assertSame(TestDefaultRepoFile::class, $genericGridFSRepository->getClassName());

        // custom service document repository
        $customServiceDocumentRepo = $dm->getRepository(TestCustomServiceRepoDocument::class);
        $this->assertSame($customServiceDocumentRepo, $this->container->get('test_alias__' . TestCustomServiceRepoDocumentRepository::class));
        // a smoke test, trying some methods
        $this->assertSame(TestCustomServiceRepoDocument::class, $customServiceDocumentRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customServiceDocumentRepo->createQueryBuilder());

        // custom service GridFS repository
        $customServiceGridFSRepo = $dm->getRepository(TestCustomServiceRepoFile::class);
        $this->assertSame($customServiceGridFSRepo, $this->container->get('test_alias__' . TestCustomServiceRepoGridFSRepository::class));
        // a smoke test, trying some methods
        $this->assertSame(TestCustomServiceRepoFile::class, $customServiceGridFSRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customServiceGridFSRepo->createQueryBuilder());
    }

    public function testInstantiatingServiceRepositoryForUnmappedClass(): void
    {
        $this->expectExceptionMessage(sprintf(
            'Could not find the document manager for class "%s".'
            . ' Check your Doctrine configuration to make sure it is configured to load this document’s metadata.',
            TestUnmappedDocument::class,
        ));
        $this->expectException(LogicException::class);
        new TestUnmappedDocumentRepository($this->container->get('doctrine_mongodb'));
    }
}
