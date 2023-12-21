<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\MappingException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

abstract class AbstractDriverTestCase extends TestCase
{
    public function testFindMappingFile(): void
    {
        $driver = $this->getDriver([
            'foo' => 'MyNamespace\MyBundle\DocumentFoo',
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ]);

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo'),
        );
    }

    public function testFindMappingFileInSubnamespace(): void
    {
        $driver = $this->getDriver([$this->getFixtureDir() => 'MyNamespace\MyBundle\Document']);

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo.Bar' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo\Bar'),
        );
    }

    public function testFindMappingFileNamespacedFoundFileNotFound(): void
    {
        $driver = $this->getDriver([$this->getFixtureDir() => 'MyNamespace\MyBundle\Document']);

        $locator = $this->getDriverLocator($driver);

        $this->expectException(MappingException::class);

        $locator->findMappingFile('MyNamespace\MyBundle\Document\Missing');
    }

    public function testFindMappingNamespaceNotFound(): void
    {
        $driver = $this->getDriver([$this->getFixtureDir() => 'MyNamespace\MyBundle\Document']);

        $locator = $this->getDriverLocator($driver);

        $this->expectException(MappingException::class);

        $locator->findMappingFile('MyOtherNamespace\MyBundle\Document\Foo');
    }

    abstract protected function getFileExtension(): string;

    abstract protected function getFixtureDir(): string;

    abstract protected function getDriver(array $paths = []): FileDriver;

    private function getDriverLocator(FileDriver $driver): FileLocator
    {
        $ref = new ReflectionProperty($driver, 'locator');
        $ref->setAccessible(true);

        return $ref->getValue($driver);
    }
}
