<?php


namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\YamlDriver;

/**
 * @group legacy
 */
class YamlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension()
    {
        return '.mongodb.yml';
    }

    protected function getFixtureDir()
    {
        return __DIR__ . '/Fixtures/yml';
    }

    protected function getDriver(array $prefixes = [])
    {
        return new YamlDriver($prefixes, $this->getFileExtension());
    }
}
