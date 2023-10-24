<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\PersistentCollectionCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionGenerator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function sys_get_temp_dir;

class PersistentCollectionCacheWarmerTest extends TestCase
{
    private ContainerInterface $container;

    /** @var PersistentCollectionGenerator&MockObject  */
    private PersistentCollectionGenerator $generatorMock;

    private PersistentCollectionCacheWarmer $warmer;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.persistent_collection_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_persistent_collection_classes', Configuration::AUTOGENERATE_NEVER);

        $this->generatorMock = $this->getMockBuilder(PersistentCollectionGenerator::class)->getMock();

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Cache']);
        $dm->getConfiguration()->setPersistentCollectionGenerator($this->generatorMock);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registryStub->method('getManagers')->willReturn([$dm]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new PersistentCollectionCacheWarmer($this->container);
    }

    public function testWarmerNotOptional(): void
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted(): void
    {
        $this->generatorMock->expects($this->exactly(2))->method('generateClass');
        $this->warmer->warmUp('meh');
    }

    /** @dataProvider provideWarmerNotExecuted */
    public function testWarmerNotExecuted(int $autoGenerate): void
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_persistent_collection_classes', $autoGenerate);
        $this->generatorMock->expects($this->exactly(0))->method('generateClass');
        $this->warmer->warmUp('meh');
    }

    public static function provideWarmerNotExecuted(): array
    {
        return [
            [ Configuration::AUTOGENERATE_ALWAYS ],
            [ Configuration::AUTOGENERATE_EVAL ],
            [ Configuration::AUTOGENERATE_FILE_NOT_EXISTS ],
        ];
    }
}
