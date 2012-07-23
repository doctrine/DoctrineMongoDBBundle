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

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array());

        $defaults = array(
            'auto_generate_hydrator_classes' => false,
            'auto_generate_proxy_classes'    => false,
            'default_commit_options'         => array(
                'safe' => true,
                'fsync' => false,
                'timeout' => \MongoCursor::$timeout,
            ),
            'default_database'               => 'default',
            'document_managers'              => array(),
            'connections'                    => array(),
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Proxies',
            'proxy_namespace'                => 'MongoDBODMProxies',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators',
            'hydrator_namespace'             => 'Hydrators',
        );

        foreach ($defaults as $key => $default) {
            $this->assertTrue(array_key_exists($key, $options), sprintf('The default "%s" exists', $key));
            $this->assertEquals($default, $options[$key]);

            unset($options[$key]);
        }

        if (count($options)) {
            $this->fail('Extra defaults were returned: '. print_r($options, true));
        }
    }

    /**
     * Tests a full configuration.
     *
     * @dataProvider fullConfigurationProvider
     */
    public function testFullConfiguration($config)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($config));

        $expected = array(
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Proxies',
            'proxy_namespace'                => 'Test_Proxies',
            'auto_generate_proxy_classes'    => true,
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators',
            'hydrator_namespace'             => 'Test_Hydrators',
            'auto_generate_hydrator_classes' => true,
            'default_commit_options'         => array(
                'safe' => 2,
                'fsync' => false,
                'timeout' => 10,
            ),
            'default_document_manager'       => 'default_dm_name',
            'default_database'               => 'default_db_name',
            'default_connection'             => 'conn1',
            'connections'   => array(
                'conn1'       => array(
                    'server'  => 'http://server',
                    'options' => array(
                        'connect'    => true,
                        'persist'    => 'persist_val',
                        'timeout'    => 500,
                        'replicaSet' => 'foo',
                        'slaveOkay'  => true,
                        'username'   => 'username_val',
                        'password'   => 'password_val',
                    ),
                ),
                'conn2'       => array(
                    'server'  => 'http://server2',
                    'options' => array(),
                ),
            ),
            'document_managers' => array(
                'dm1' => array(
                    'logging'      => '%kernel.debug%',
                    'auto_mapping' => false,
                    'metadata_cache_driver' => array(
                        'type'           => 'memcache',
                        'class'          => 'fooClass',
                        'host'           => 'host_val',
                        'port'           => 1234,
                        'instance_class' => 'instance_val',
                    ),
                    'mappings' => array(
                        'FooBundle' => array(
                            'type'    => 'annotations',
                            'mapping' => true,
                        ),
                    ),
                    'profiler' => array(
                        'enabled' => '%kernel.debug%',
                        'pretty'  => '%kernel.debug%',
                    ),
                    'retry_connect' => 0,
                    'retry_query' => 0,
                ),
                'dm2' => array(
                    'connection'   => 'dm2_connection',
                    'database'     => 'db1',
                    'logging'      => true,
                    'auto_mapping' => false,
                    'metadata_cache_driver' => array(
                        'type' => 'apc',
                    ),
                    'mappings' => array(
                        'BarBundle' => array(
                            'type'      => 'yml',
                            'dir'       => '%kernel.cache_dir%',
                            'prefix'    => 'prefix_val',
                            'alias'     => 'alias_val',
                            'is_bundle' => false,
                            'mapping'   => true,
                        )
                    ),
                    'profiler' => array(
                        'enabled' => '%kernel.debug%',
                        'pretty'  => '%kernel.debug%',
                    ),
                    'retry_connect' => 0,
                    'retry_query' => 0,
                )
            )
        );

        $this->assertEquals($expected, $options);
    }

    public function fullConfigurationProvider()
    {
      $yaml = Yaml::parse(__DIR__.'/Fixtures/config/yml/full.yml');
      $yaml = $yaml['doctrine_mongodb'];

       return array(
           array($yaml),
       );
    }

    /**
     * @dataProvider optionProvider
     * @param array $configs The source array of configuration arrays
     * @param array $correctValues A key-value pair of end values to check
     */
    public function testMergeOptions(array $configs, array $correctValues)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, $configs);

        foreach ($correctValues as $key => $correctVal)
        {
            $this->assertEquals($correctVal, $options[$key]);
        }
    }

    public function optionProvider()
    {
        $cases = array();

        // single config, testing normal option setting
        $cases[] = array(
            array(
                array('default_document_manager' => 'foo'),
            ),
            array('default_document_manager' => 'foo')
        );

        // single config, testing normal option setting with dashes
        $cases[] = array(
            array(
                array('default-document-manager' => 'bar'),
            ),
            array('default_document_manager' => 'bar')
        );

        // testing the normal override merging - the later config array wins
        $cases[] = array(
            array(
                array('default_document_manager' => 'foo'),
                array('default_document_manager' => 'baz'),
            ),
            array('default_document_manager' => 'baz')
        );

        // the "options" array is totally replaced
        $cases[] = array(
            array(
                array('connections' => array('default' => array('options' => array('timeout' => 2000)))),
                array('connections' => array('default' => array('options' => array('username' => 'foo')))),
            ),
            array('connections' => array('default' => array('options' => array('username' => 'foo'), 'server' => null))),
        );

        // mappings are merged non-recursively.
        $cases[] = array(
            array(
                array('document_managers' => array('default' => array('mappings' => array('foomap' => array('type' => 'val1'), 'barmap' => array('dir' => 'val2'))))),
                array('document_managers' => array('default' => array('mappings' => array('barmap' => array('prefix' => 'val3'))))),
            ),
            array('document_managers' => array('default' => array('metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'mappings' => array('foomap' => array('type' => 'val1', 'mapping' => true), 'barmap' => array('prefix' => 'val3', 'mapping' => true)), 'retry_connect' => 0, 'retry_query' => 0))),
        );

        // connections are merged non-recursively.
        $cases[] = array(
            array(
                array('connections' => array('foocon' => array('server' => 'val1'), 'barcon' => array('options' => array('username' => 'val2')))),
                array('connections' => array('barcon' => array('server' => 'val3'))),
            ),
            array('connections' => array(
                'foocon' => array('server' => 'val1', 'options' => array()),
                'barcon' => array('server' => 'val3', 'options' => array())
            )),
        );

        // managers are merged non-recursively.
        $cases[] = array(
            array(
                array('document_managers' => array('foodm' => array('database' => 'val1'), 'bardm' => array('database' => 'val2'))),
                array('document_managers' => array('bardm' => array('database' => 'val3'))),
            ),
            array('document_managers' => array(
                'foodm' => array('database' => 'val1', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
                'bardm' => array('database' => 'val3', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
            )),
        );

        return $cases;
    }

    /**
     * @dataProvider getNormalizationTests
     */
    public function testNormalizeOptions(array $config, $targetKey, array $normalized)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($config));
        $this->assertEquals($normalized, $options[$targetKey]);
    }

    public function getNormalizationTests()
    {
        return array(
            // connection versus connections (id is the identifier)
            array(
                array('connection' => array(
                    array('server' => 'mongodb://abc', 'id' => 'foo'),
                    array('server' => 'mongodb://def', 'id' => 'bar'),
                )),
                'connections',
                array(
                    'foo' => array('server' => 'mongodb://abc', 'options' => array()),
                    'bar' => array('server' => 'mongodb://def', 'options' => array()),
                ),
            ),
            // document_manager versus document_managers (id is the identifier)
            array(
                array('document_manager' => array(
                    array('connection' => 'conn1', 'id' => 'foo'),
                    array('connection' => 'conn2', 'id' => 'bar'),
                )),
                'document_managers',
                array(
                    'foo' => array('connection' => 'conn1', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
                    'bar' => array('connection' => 'conn2', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
                ),
            ),
            // mapping configuration that's beneath a specific document manager
            array(
                array('document_manager' => array(
                    array('id' => 'foo', 'connection' => 'conn1', 'mapping' => array(
                        'type' => 'xml', 'name' => 'foo-mapping'
                    )),
                )),
                'document_managers',
                array(
                    'foo' => array(
                        'connection'   => 'conn1',
                        'metadata_cache_driver' => array('type' => 'array'),
                        'mappings'     => array('foo-mapping' => array('type' => 'xml', 'mapping' => true)),
                        'logging'      => '%kernel.debug%',
                        'profiler'     => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'),
                        'auto_mapping' => false,
                        'retry_connect' => 0,
                        'retry_query' => 0,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider getInvalidSafeCommitOptions
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidSafeCommitOptions($safeCommitOption)
    {
        $invalidConfig = array('default_commit_options' => array('safe' => $safeCommitOption));

        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($invalidConfig));
    }

    public function getInvalidSafeCommitOptions()
    {
        return array(
            array('NaN'),
            array(-1.0),
        );
    }

    /**
     * @dataProvider getInvalidTimeoutCommitOptions
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidTimeoutCommitOptions($timeoutCommitOption)
    {
        $invalidConfig = array('default_commit_options' => array('timeout' => $timeoutCommitOption));

        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($invalidConfig));
    }

    public function getInvalidTimeoutCommitOptions()
    {
        return array(
            array('NaN'),
            array(-2),
        );
    }
}
