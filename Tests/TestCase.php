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

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Mongo PHP/PECL Extension is not available.');
        }
        if (!class_exists('Doctrine\\ODM\\MongoDB\\Version')) {
            $this->markTestSkipped('Doctrine MongoDB ODM is not available.');
        }
        try {
            new \Mongo();
        } catch (\MongoException $e) {
            $this->markTestSkipped('Unable to connect to Mongo.');
        }
    }

    /**
     * @return DocumentManager
     */
    public static function createTestDocumentManager($paths = array())
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setHydratorDir(\sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setHydratorNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), $paths));
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

        return DocumentManager::create(new Connection(), $config);
    }
}
