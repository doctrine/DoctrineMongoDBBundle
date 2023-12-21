<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

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

        return DocumentManager::create(null, $config);
    }
}
