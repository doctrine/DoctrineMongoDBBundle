<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class YamlMongoDBExtensionTest extends AbstractMongoDBExtensionTestCase
{
    protected function loadFromFile(ContainerBuilder $container, string $file): void
    {
        $loadYaml = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config/yml'));
        $loadYaml->load($file . '.yml');
    }
}
