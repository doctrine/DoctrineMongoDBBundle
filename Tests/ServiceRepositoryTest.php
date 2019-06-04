<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Query\Builder;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomClassRepoDocument;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoDocument;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestDefaultRepoDocument;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomClassRepoRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestUnmappedDocumentRepository;
use Fixtures\Bundles\RepositoryServiceBundle\RepositoryServiceBundle;
use LogicException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ServiceRepositoryTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    protected function setUp()
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
        $this->container->setDefinition('annotation_reader', new Definition(AnnotationReader::class));
        $extension = new DoctrineMongoDBExtension();
        $this->container->registerExtension($extension);

        $extension->load([[
            'connections' => ['default' => []],
            'document_managers' => ['default' => [
                'mappings' => [
                    'RepositoryServiceBundle' => [
                        'type' => 'annotation',
                        'dir' => __DIR__ . '/DependencyInjection/Fixtures/Bundles/RepositoryServiceBundle/Document',
                        'prefix' => 'Fixtures\Bundles\RepositoryServiceBundle\Document',
                    ],
                ],
            ]],
        ]], $this->container);

        $def = $this->container->register(TestCustomServiceRepoRepository::class, TestCustomServiceRepoRepository::class)
            ->setPublic(false);
        // create a public alias so we can use it below for testing
        $this->container->setAlias('test_alias__' . TestCustomServiceRepoRepository::class, new Alias(TestCustomServiceRepoRepository::class, true));

        $def->setAutowired(true);
        $def->setAutoconfigured(true);

        $this->container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $this->container->compile();
    }

    public function testRepositoryServiceWiring()
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
        $this->assertSame($genericRepository, $genericRepository = $dm->getRepository(TestDefaultRepoDocument::class));
        // a smoke test, trying one of the methods
        $this->assertSame(TestDefaultRepoDocument::class, $genericRepository->getClassName());

        // custom service repository
        $customServiceRepo = $dm->getRepository(TestCustomServiceRepoDocument::class);
        $this->assertSame($customServiceRepo, $this->container->get('test_alias__' . TestCustomServiceRepoRepository::class));
        // a smoke test, trying some methods
        $this->assertSame(TestCustomServiceRepoDocument::class, $customServiceRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customServiceRepo->createQueryBuilder());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Could not find the document manager for class "Fixtures\Bundles\RepositoryServiceBundle\Document\TestUnmappedDocument". Check your Doctrine configuration to make sure it is configured to load this document’s metadata.
     */
    public function testInstantiatingServiceRepositoryForUnmappedClass()
    {
        new TestUnmappedDocumentRepository($this->container->get('doctrine_mongodb'));
    }
}
