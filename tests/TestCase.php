<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use MongoDB\Client;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function getenv;
use function sys_get_temp_dir;

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
        $uri = getenv('DOCTRINE_MONGODB_SERVER') ?: 'mongodb://localhost:27017';

        return DocumentManager::create(new Client($uri), $config);
    }
}
