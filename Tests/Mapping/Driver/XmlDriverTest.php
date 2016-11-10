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
