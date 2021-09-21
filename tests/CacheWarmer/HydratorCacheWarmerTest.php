<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\HydratorCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\Configuration;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function method_exists;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;

class HydratorCacheWarmerTest extends TestCase
{
    /** @var ContainerInterface */
    private $container;

    /** @var HydratorCacheWarmer */
    private $warmer;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.hydrator_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes', Configuration::AUTOGENERATE_NEVER);

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Validator']);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registryStub->method('getManagers')->willReturn([$dm]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new HydratorCacheWarmer($this->container);
    }

    public function testWarmerNotOptional(): void
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted(): void
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
    public function testWarmerNotExecuted(int $autoGenerate): void
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes', $autoGenerate);
        $hydratorFilename = $this->getHydratorFilename();

        try {
            $this->warmer->warmUp('meh');
            // Replace by "assertFileDoesNotExist" when PHPUnit 9 is minimum
            $this->assertFileDoesNotExistWithBC($hydratorFilename);
        } finally {
            @unlink($hydratorFilename);
        }
    }

    private function assertFileDoesNotExistWithBC(string $filename): void
    {
        if (! method_exists($this, 'assertFileDoesNotExist')) {
            $this->assertFileNotExists($filename);

            return;
        }

        $this->assertFileDoesNotExist($filename);
    }

    /**
     * @return array<array{int}>
     */
    public function provideWarmerNotExecuted(): array
    {
        return [
            [ Configuration::AUTOGENERATE_ALWAYS ],
            [ Configuration::AUTOGENERATE_EVAL ],
            [ Configuration::AUTOGENERATE_FILE_NOT_EXISTS ],
        ];
    }

    private function getHydratorFilename(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'DoctrineBundleMongoDBBundleTestsFixturesValidatorDocumentHydrator.php';
    }
}
