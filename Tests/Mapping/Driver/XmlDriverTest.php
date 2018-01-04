<?php


namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;

class XmlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension()
    {
        return '.mongodb.xml';
    }

    protected function getFixtureDir()
    {
        return __DIR__ . '/Fixtures/xml';
    }

    protected function getDriver(array $prefixes = [])
    {
        return new XmlDriver($prefixes, $this->getFileExtension());
    }
}
