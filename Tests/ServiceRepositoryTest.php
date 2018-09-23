<?php

namespace Doctrine\Bundle\DoctrineBundle\Tests;

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

        $def = $container->register(TestCustomServiceRepoRepository::class, TestCustomServiceRepoRepository::class)
            ->setPublic(false);
        // create a public alias so we can use it below for testing
        $container->setAlias('test_alias__' . TestCustomServiceRepoRepository::class, new Alias(TestCustomServiceRepoRepository::class, true));

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
        $genericRepository = $em->getRepository(TestDefaultRepoDocument::class);
        $this->assertInstanceOf(DocumentRepository::class, $genericRepository);
        $this->assertSame($genericRepository, $genericRepository = $em->getRepository(TestDefaultRepoDocument::class));
        // a smoke test, trying one of the methods
        $this->assertSame(TestDefaultRepoDocument::class, $genericRepository->getClassName());

        // custom service repository
        $customServiceRepo = $em->getRepository(TestCustomServiceRepoDocument::class);
        $this->assertSame($customServiceRepo, $container->get('test_alias__' . TestCustomServiceRepoRepository::class));
        // a smoke test, trying some methods
        $this->assertSame(TestCustomServiceRepoDocument::class, $customServiceRepo->getClassName());
        $this->assertInstanceOf(Builder::class, $customServiceRepo->createQueryBuilder());
    }
}
