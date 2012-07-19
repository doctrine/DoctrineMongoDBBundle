<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testFindMappingFile()
    {
        $driver = $this->getDriver(array(
            'foo' => 'MyNamespace\MyBundle\DocumentFoo',
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ));

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo')
        );
    }

    public function testFindMappingFileInSubnamespace()
    {
        $driver = $this->getDriver(array(
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ));

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo.Bar' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo\Bar')
        );
    }

    /**
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testFindMappingFileNamespacedFoundFileNotFound()
    {
        $driver = $this->getDriver(array(
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ));

        $locator = $this->getDriverLocator($driver);
        $locator->findMappingFile('MyNamespace\MyBundle\Document\Missing');
    }

    /**
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testFindMappingNamespaceNotFound()
    {
        $driver = $this->getDriver(array(
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ));

        $locator = $this->getDriverLocator($driver);
        $locator->findMappingFile('MyOtherNamespace\MyBundle\Document\Foo');
    }

    abstract protected function getFileExtension();
    abstract protected function getFixtureDir();
    abstract protected function getDriver(array $paths = array());

    private function getDriverLocator(FileDriver $driver)
    {
        $ref = new \ReflectionProperty($driver, 'locator');
        $ref->setAccessible(true);

        return $ref->getValue($driver);
    }
}
