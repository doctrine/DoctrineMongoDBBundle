<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testFindMappingFile()
    {
        $driver = $this->getDriver([
            'foo' => 'MyNamespace\MyBundle\DocumentFoo',
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ]);

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo')
        );
    }

    public function testFindMappingFileInSubnamespace()
    {
        $driver = $this->getDriver([
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ]);

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
        $driver = $this->getDriver([
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ]);

        $locator = $this->getDriverLocator($driver);
        $locator->findMappingFile('MyNamespace\MyBundle\Document\Missing');
    }

    /**
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testFindMappingNamespaceNotFound()
    {
        $driver = $this->getDriver([
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ]);

        $locator = $this->getDriverLocator($driver);
        $locator->findMappingFile('MyOtherNamespace\MyBundle\Document\Foo');
    }

    abstract protected function getFileExtension();
    abstract protected function getFixtureDir();
    abstract protected function getDriver(array $paths = []);

    private function getDriverLocator(FileDriver $driver)
    {
        $ref = new \ReflectionProperty($driver, 'locator');
        $ref->setAccessible(true);

        return $ref->getValue($driver);
    }
}
