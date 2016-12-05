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
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ContainerTest extends TestCase
{
    private $container;
    private $extension;

    protected function setUp()
    {
        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles'      => [],
            'kernel.cache_dir'    => sys_get_temp_dir(),
            'kernel.root_dir'     => sys_get_temp_dir(),
            'kernel.environment'  => 'test',
            'kernel.name'         => 'kernel',
            'kernel.debug'        => true,
        ]));

        $this->container->setDefinition('annotation_reader', new Definition(AnnotationReader::class));
        $this->extension = new DoctrineMongoDBExtension();
    }

    /**
     * @dataProvider provideLoggerConfigs
     */
    public function testLoggerConfig($config, $logger, $debug)
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->extension->load([$config], $this->container);

        $def = $this->container->getDefinition('doctrine_mongodb.odm.default_configuration');
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
        $config = ['connections' => ['default' => []]];

        return [
            [
                // logging and profiler default to true when in debug mode
                ['document_managers' => ['default' => []]] + $config,
                'doctrine_mongodb.odm.logger.aggregate',
                true,
            ],
            [
                // logging and profiler default to false when not in debug mode
                ['document_managers' => ['default' => []]] + $config,
                false,
                false,
            ],
            [
                ['document_managers' => ['default' => ['logging' => true, 'profiler' => true]]] + $config,
                'doctrine_mongodb.odm.logger.aggregate',
                true,
            ],
            [
                ['document_managers' => ['default' => ['logging' => false, 'profiler' => true]]] + $config,
                'doctrine_mongodb.odm.data_collector.pretty',
                true,
            ],
            [
                ['document_managers' => ['default' => ['logging' => true, 'profiler' => false]]] + $config,
                'doctrine_mongodb.odm.logger',
                true,
            ],
            [
                ['document_managers' => ['default' => ['logging' => false, 'profiler' => false]]] + $config,
                false,
                true,
            ],
        ];
    }
}
