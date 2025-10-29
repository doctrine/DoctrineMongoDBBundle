<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use MongoDB\Client;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function getenv;
use function method_exists;
use function sys_get_temp_dir;

use const PHP_VERSION_ID;

class TestCase extends BaseTestCase
{
    /** @param string[] $paths */
    public static function createTestDocumentManager(array $paths = []): DocumentManager
    {
        $config = new Configuration();
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_FILE_NOT_EXISTS);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setHydratorNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AttributeDriver($paths));
        $config->setMetadataCache(new ArrayAdapter());

        if (PHP_VERSION_ID >= 80400 && method_exists($config, 'setUseLazyGhostObject')) {
            $config->setUseLazyGhostObject(true);
        } elseif (method_exists($config, 'setUseLazyGhostObject')) {
            $config->setUseLazyGhostObject(false);
        }

        $uri = getenv('MONGODB_URI') ?: throw new RuntimeException('The MONGODB_URI environment variable is not set.');

        return DocumentManager::create(new Client($uri), $config);
    }
}
