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

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineMongoDBExtensionTest extends \PHPUnit_Framework_TestCase
{

    public function testBackwardCompatibilityAliases()
    {
        $loader = new DoctrineMongoDBExtension();
        $loader->load(array(), $container = new ContainerBuilder());

        $this->assertEquals('doctrine_mongodb.odm.document_manager', (string) $container->getAlias('doctrine.odm.mongodb.document_manager'));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testParameterOverride($option, $parameter, $value)
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(array(array($option => $value)), $container);

        $this->assertEquals($value, $container->getParameter('doctrine_mongodb.odm.'.$parameter));
    }

    public function parameterProvider()
    {
        return array(
            array('proxy_namespace', 'proxy_namespace', 'foo'),
            array('proxy-namespace', 'proxy_namespace', 'bar'),
        );
    }
}
