<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use PHPUnit\Framework\TestCase as BaseTestCase;
use function sys_get_temp_dir;

class TestCase extends BaseTestCase
{
    /**
     * @return DocumentManager
     */
    public static function createTestDocumentManager($paths = [])
    {
        $config = new Configuration();
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_FILE_NOT_EXISTS);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setHydratorNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), $paths));
        $config->setMetadataCacheImpl(new ArrayCache());

        return DocumentManager::create(null, $config);
    }
}
