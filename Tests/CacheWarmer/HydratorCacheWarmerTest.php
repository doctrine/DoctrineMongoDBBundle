<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\HydratorCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\Configuration;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use const DIRECTORY_SEPARATOR;
use function sys_get_temp_dir;
use function unlink;

class HydratorCacheWarmerTest extends TestCase
{
    /** @var ContainerInterface */
    private $container;

    /** @var HydratorCacheWarmer */
    private $warmer;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.hydrator_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes', Configuration::AUTOGENERATE_NEVER);

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Validator']);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registryStub->expects($this->any())->method('getManagers')->willReturn([ $dm ]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new HydratorCacheWarmer($this->container);
    }

    public function testWarmerNotOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted()
    {
        $hydratorFilename = $this->getHydratorFilename();

        try {
            $this->warmer->warmUp('meh');
            $this->assertFileExists($hydratorFilename);
        } finally {
            @unlink($hydratorFilename);
        }
    }

    /**
     * @dataProvider provideWarmerNotExecuted
     */
    public function testWarmerNotExecuted($autoGenerate)
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes', $autoGenerate);
        $hydratorFilename = $this->getHydratorFilename();

        try {
            $this->warmer->warmUp('meh');
            $this->assertFileNotExists($hydratorFilename);
        } finally {
            @unlink($hydratorFilename);
        }
    }

    public function provideWarmerNotExecuted()
    {
        return [
            [ Configuration::AUTOGENERATE_ALWAYS ],
            [ Configuration::AUTOGENERATE_EVAL ],
            [ Configuration::AUTOGENERATE_FILE_NOT_EXISTS ],
        ];
    }

    private function getHydratorFilename() : string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'DoctrineBundleMongoDBBundleTestsFixturesValidatorDocumentHydrator.php';
    }
}
