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

use Symfony\Bundle\DoctrineMongoDBBundle\Mapping\Driver\YamlDriver;

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

    protected function getDriver(array $prefixes = array())
    {
        return new YamlDriver($prefixes, $this->getFileExtension());
    }
}
