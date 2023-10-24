<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;

class XmlDriverTest extends AbstractDriverTestCase
{
    protected function getFileExtension(): string
    {
        return '.mongodb.xml';
    }

    protected function getFixtureDir(): string
    {
        return __DIR__ . '/Fixtures/xml';
    }

    protected function getDriver(array $paths = []): XmlDriver
    {
        return new XmlDriver($paths, $this->getFileExtension());
    }
}
