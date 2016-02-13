<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\PersistentCollectionCacheWarmer;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionGenerator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PersistentCollectionCacheWarmerTest extends \Doctrine\Bundle\MongoDBBundle\Tests\TestCase
{
    /** @var ContainerInterface */
    private $container;

    private $generatorMock;

    /** @var PersistentCollectionCacheWarmer */
    private $warmer;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.persistent_collection_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_persistent_collection_classes', Configuration::AUTOGENERATE_NEVER);

        $this->generatorMock = $this->getMockBuilder(PersistentCollectionGenerator::class)->getMock();

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Cache']);
        $dm->getConfiguration()->setPersistentCollectionGenerator($this->generatorMock);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registryStub->expects($this->any())->method('getManagers')->willReturn([ $dm ]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new PersistentCollectionCacheWarmer($this->container);
    }

    public function testWarmerNotOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted()
    {
        $this->generatorMock->expects($this->exactly(2))->method('generateClass');
        $this->warmer->warmUp('meh');
    }

    /**
     * @dataProvider provideWarmerNotExecuted
     */
    public function testWarmerNotExecuted($autoGenerate)
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_persistent_collection_classes', $autoGenerate);
        $this->generatorMock->expects($this->exactly(0))->method('generateClass');
        $this->warmer->warmUp('meh');
    }

    public function provideWarmerNotExecuted()
    {
        return [
            [ Configuration::AUTOGENERATE_ALWAYS ],
            [ Configuration::AUTOGENERATE_EVAL ],
            [ Configuration::AUTOGENERATE_FILE_NOT_EXISTS ],
        ];
    }
}
