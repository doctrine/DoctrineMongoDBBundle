<?php

namespace Doctrine\Bundle\DoctrineBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomClassRepoDocument;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoDocument;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoFile;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestDefaultRepoDocument;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestDefaultRepoFile;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomClassRepoRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoDocumentRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoGridFSRepository;
use Fixtures\Bundles\RepositoryServiceBundle\RepositoryServiceBundle;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ServiceRepositoryTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (class_exists(DocumentManager::class)) {
            return;
        }

        $this->markTestSkipped('Doctrine MongoDB ODM is not available.');
    }

    public function testRepositoryServiceWiring()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.name' => 'app',
            'kernel.debug' => false,
            'kernel.bundles' => ['RepositoryServiceBundle' => RepositoryServiceBundle::class],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../../../', // src dir
        ]));
        $container->setDefinition('annotation_reader', new Definition(AnnotationReader::class));
        $extension = new DoctrineMongoDBExtension();
        $container->registerExtension($extension);

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
        ]], $container);

        $def = $container->register(TestCustomServiceRepoDocumentRepository::class, TestCustomServiceRepoDocumentRepository::class)
            ->setPublic(false);
        // create a public alias so we can use it below for testing
        $container->setAlias('test_alias__' . TestCustomServiceRepoDocumentRepository::class, new Alias(TestCustomServiceRepoDocumentRepository::class, true));

        $def->setAutowired(true);
        $def->setAutoconfigured(true);

        $def = $container->register(TestCustomServiceRepoGridFSRepository::class, TestCustomServiceRepoGridFSRepository::class)
            ->setPublic(false);
        // create a public alias so we can use it below for testing
        $container->setAlias('test_alias__' . TestCustomServiceRepoGridFSRepository::class, new Alias(TestCustomServiceRepoGridFSRepository::class, true));

        $def->setAutowired(true);
        $def->setAutoconfigured(true);

        $container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $container->compile();

        $em = $container->get('doctrine_mongodb.odm.document_manager');

        // traditional custom class repository
        $customClassRepo = $em->getRepository(TestCustomClassRepoDocument::class);
        $this->assertInstanceOf(TestCustomClassRepoRepository::class, $customClassRepo);
        // a smoke test, trying some methods
        $this->assertSame(TestCustomClassRepoDocument::class, $customClassRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customClassRepo->createQueryBuilder());

        // generic DocumentRepository
        $genericDocumentRepository = $em->getRepository(TestDefaultRepoDocument::class);
        $this->assertInstanceOf(DocumentRepository::class, $genericDocumentRepository);
        // a smoke test, trying one of the methods
        $this->assertSame(TestDefaultRepoDocument::class, $genericDocumentRepository->getClassName());

        // generic GridFSRepository
        $genericGridFSRepository = $em->getRepository(TestDefaultRepoFile::class);
        $this->assertInstanceOf(DefaultGridFSRepository::class, $genericGridFSRepository);
        // a smoke test, trying one of the methods
        $this->assertSame(TestDefaultRepoFile::class, $genericGridFSRepository->getClassName());

        // custom service document repository
        $customServiceDocumentRepo = $em->getRepository(TestCustomServiceRepoDocument::class);
        $this->assertSame($customServiceDocumentRepo, $container->get('test_alias__' . TestCustomServiceRepoDocumentRepository::class));
        // a smoke test, trying some methods
        $this->assertSame(TestCustomServiceRepoDocument::class, $customServiceDocumentRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customServiceDocumentRepo->createQueryBuilder());

        // custom service GridFS repository
        $customServiceGridFSRepo = $em->getRepository(TestCustomServiceRepoFile::class);
        $this->assertSame($customServiceGridFSRepo, $container->get('test_alias__' . TestCustomServiceRepoGridFSRepository::class));
        // a smoke test, trying some methods
        $this->assertSame(TestCustomServiceRepoFile::class, $customServiceGridFSRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customServiceGridFSRepo->createQueryBuilder());
    }
}
