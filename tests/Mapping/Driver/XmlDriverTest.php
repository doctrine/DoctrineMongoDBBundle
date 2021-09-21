<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;

class XmlDriverTest extends AbstractDriverTest
{
    /**
     * @return string
     */
    protected function getFileExtension()
    {
        return '.mongodb.xml';
    }

    /**
     * @return string
     */
    protected function getFixtureDir()
    {
        return __DIR__ . '/Fixtures/xml';
    }

    /**
     * @return XmlDriver
     */
    protected function getDriver(array $paths = [])
    {
        return new XmlDriver($paths, $this->getFileExtension());
    }
}
