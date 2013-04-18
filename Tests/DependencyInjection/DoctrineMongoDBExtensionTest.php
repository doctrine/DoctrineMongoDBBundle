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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DoctrineMongoDBExtensionTest extends \PHPUnit_Framework_TestCase
{
    public static function buildConfiguration(array $settings = array())
    {
        return array(array_merge(
            array(
                'connections' => array('dummy' => array()),
                'document_managers' => array('dummy' => array()),
            ),
            $settings
        ));
    }

    public function buildMinimalContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.root_dir'    => __DIR__,
            'kernel.environment' => 'test',
            'kernel.debug'       => 'true',
        )));
        return $container;
    }

    public function testBackwardCompatibilityAliases()
    {
        $loader = new DoctrineMongoDBExtension();

        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $this->assertEquals('doctrine_mongodb.odm.document_manager', (string) $container->getAlias('doctrine.odm.mongodb.document_manager'));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testParameterOverride($option, $parameter, $value)
    {
        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(array($option => $value)), $container);

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
