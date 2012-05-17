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

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ContainerTest extends TestCase
{
    private $container;
    private $extension;

    protected function setUp()
    {
        $this->container = new ContainerBuilder(new ParameterBag(array(
            'kernel.bundles'   => array(),
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.root_dir'  => sys_get_temp_dir(),
            'kernel.environment'  => 'test',
            'kernel.debug'     => true,
        )));

        $this->container->setDefinition('annotation_reader', new Definition('Doctrine\Common\Annotations\AnnotationReader'));
        $this->extension = new DoctrineMongoDBExtension();
    }

    /**
     * @dataProvider provideLoggerConfigs
     */
    public function testLoggerConfig($config, $logger, $debug)
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->extension->load(array($config), $this->container);

        $def = $this->container->getDefinition('doctrine.odm.mongodb.default_configuration');
        if (false === $logger) {
            $this->assertFalse($def->hasMethodCall('setLoggerCallable'));
        } else {
            $match = null;
            foreach ($def->getMethodCalls() as $call) {
                if ('setLoggerCallable' == $call[0]) {
                    $match = (string) $call[1][0][0];
                    break;
                }
            }
            $this->assertEquals($logger, $match, 'Service "'.$logger.'" is set as the logger');
        }
    }

    public function provideLoggerConfigs()
    {
        $config = array('connections' => array('default' => array()));

        return array(
            array(
                // logging and profiler default to true when in debug mode
                array('document_managers' => array('default' => array())) + $config,
                'doctrine.odm.mongodb.logger.aggregate',
                true,
            ),
            array(
                // logging and profiler default to false when not in debug mode
                array('document_managers' => array('default' => array())) + $config,
                false,
                false,
            ),
            array(
                array('document_managers' => array('default' => array('logging' => true, 'profiler' => true))) + $config,
                'doctrine.odm.mongodb.logger.aggregate',
                true,
            ),
            array(
                array('document_managers' => array('default' => array('logging' => false, 'profiler' => true))) + $config,
                'doctrine.odm.mongodb.data_collector.pretty',
                true,
            ),
            array(
                array('document_managers' => array('default' => array('logging' => true, 'profiler' => false))) + $config,
                'doctrine.odm.mongodb.logger',
                true,
            ),
            array(
                array('document_managers' => array('default' => array('logging' => false, 'profiler' => false))) + $config,
                false,
                true,
            ),
        );
    }
}
