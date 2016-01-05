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
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array());

        $defaults = array(
            'fixture_loader'                 => 'Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader',
            'auto_generate_hydrator_classes' => false,
            'auto_generate_proxy_classes'    => false,
            'default_database'               => 'default',
            'document_managers'              => array(),
            'connections'                    => array(),
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Proxies',
            'resolve_target_documents'       => array(),
            'proxy_namespace'                => 'MongoDBODMProxies',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators',
            'hydrator_namespace'             => 'Hydrators',
            'default_commit_options'         => array(),
        );

        $this->assertEquals($defaults, $options);
    }

    /**
     * @dataProvider provideFullConfiguration
     */
    public function testFullConfiguration($config)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($config));

        $expected = array(
            'fixture_loader'                 => 'Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader',
            'auto_generate_hydrator_classes' => true,
            'auto_generate_proxy_classes'    => true,
            'default_connection'             => 'conn1',
            'default_database'               => 'default_db_name',
            'default_document_manager'       => 'default_dm_name',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Hydrators',
            'hydrator_namespace'             => 'Test_Hydrators',
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Proxies',
            'proxy_namespace'                => 'Test_Proxies',
            'default_commit_options' => array(
                'j' => false,
                'timeout' => 10,
                'w' => 'majority',
                'wtimeout' => 10,
                'fsync' => false,
                'safe' => 2,
            ),
            'connections' => array(
                'conn1' => array(
                    'server'  => 'mongodb://localhost',
                    'options' => array(
                        'connect'           => true,
                        'connectTimeoutMS'  => 500,
                        'db'                => 'database_val',
                        'journal'           => true,
                        'password'          => 'password_val',
                        'readPreference'    => 'secondaryPreferred',
                        'readPreferenceTags' => array(
                            array('dc' => 'east', 'use' => 'reporting'),
                            array('dc' => 'west'),
                            array(),
                        ),
                        'replicaSet'        => 'foo',
                        'slaveOkay'         => true,
                        'socketTimeoutMS'   => 1000,
                        'ssl'               => true,
                        'authMechanism'     => 'X509',
                        'username'          => 'username_val',
                        'w'                 => 'majority',
                        'wTimeoutMS'        => 1000,
                    ),
                ),
                'conn2' => array(
                    'server'  => 'mongodb://otherhost',
                ),
            ),
            'document_managers' => array(
                'dm1' => array(
                    'default_repository_class' => 'Doctrine\ODM\MongoDB\DocumentRepository',
                    'repository_factory' => null,
                    'logging'      => '%kernel.debug%',
                    'auto_mapping' => false,
                    'filters' => array(
                        'disabled_filter' => array(
                            'class' => 'Vendor\Filter\DisabledFilter',
                            'enabled' => false,
                            'parameters' => array(),
                        ),
                        'basic_filter' => array(
                            'class' => 'Vendor\Filter\BasicFilter',
                            'enabled' => true,
                            'parameters' => array(),
                        ),
                        'complex_filter' => array(
                            'class' => 'Vendor\Filter\ComplexFilter',
                            'enabled' => true,
                            'parameters' => array(
                                'integer' => 1,
                                'string' => 'foo',
                                'object' => array('key' => 'value'),
                                'array' => array(1, 2, 3),
                            ),
                        ),
                    ),
                    'metadata_cache_driver' => array(
                        'type'           => 'memcache',
                        'class'          => 'fooClass',
                        'host'           => 'host_val',
                        'port'           => 1234,
                        'instance_class' => 'instance_val',
                    ),
                    'mappings' => array(
                        'FooBundle' => array(
                            'type'    => 'annotation',
                            'mapping' => true,
                        ),
                    ),
                    'profiler' => array(
                        'enabled' => true,
                        'pretty'  => false,
                    ),
                    'retry_connect' => 0,
                    'retry_query' => 0,
                ),
                'dm2' => array(
                    'connection'   => 'dm2_connection',
                    'database'     => 'db1',
                    'logging'      => true,
                    'default_repository_class' => 'Foo\Bar\CustomRepository',
                    'repository_factory' => null,
                    'auto_mapping' => false,
                    'filters'      => array(),
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
                        ),
                    ),
                    'profiler' => array(
                        'enabled' => '%kernel.debug%',
                        'pretty'  => '%kernel.debug%',
                    ),
                    'retry_connect' => 0,
                    'retry_query' => 0,
                ),
            ),
            'resolve_target_documents' => array(
                'Foo\BarInterface' => 'Bar\FooClass'
            ),
        );

        $this->assertEquals($expected, $options);
    }

    public function provideFullConfiguration()
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__.'/Fixtures/config/yml/full.yml'));
        $yaml = $yaml['doctrine_mongodb'];

        $xml = XmlUtils::loadFile(__DIR__.'/Fixtures/config/xml/full.xml');
        $xml = XmlUtils::convertDomElementToArray($xml->getElementsByTagName('config')->item(0));

        return array(
            array($yaml),
            array($xml),
        );
    }

    /**
     * @dataProvider provideMergeOptions
     * @param array $configs  An array of configuration arrays to process
     * @param array $expected Array of key/value options expected in the processed configuration
     */
    public function testMergeOptions(array $configs, array $expected)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, $configs);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    public function provideMergeOptions()
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
            array('connections' => array('default' => array('options' => array('username' => 'foo')))),
        );

        // mappings are merged non-recursively.
        $cases[] = array(
            array(
                array('document_managers' => array('default' => array('mappings' => array('foomap' => array('type' => 'val1'), 'barmap' => array('dir' => 'val2'))))),
                array('document_managers' => array('default' => array('mappings' => array('barmap' => array('prefix' => 'val3'))))),
            ),
            array('document_managers' => array('default' => array('metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'default_repository_class' =>  'Doctrine\ODM\MongoDB\DocumentRepository', 'repository_factory' => null, 'filters' => array(), 'mappings' => array('foomap' => array('type' => 'val1', 'mapping' => true), 'barmap' => array('prefix' => 'val3', 'mapping' => true)), 'retry_connect' => 0, 'retry_query' => 0))),
        );

        // connections are merged non-recursively.
        $cases[] = array(
            array(
                array('connections' => array('foocon' => array('server' => 'val1'), 'barcon' => array('options' => array('username' => 'val2')))),
                array('connections' => array('barcon' => array('server' => 'val3'))),
            ),
            array('connections' => array(
                'foocon' => array('server' => 'val1'),
                'barcon' => array('server' => 'val3'),
            )),
        );

        // connection options are merged non-recursively.
        $cases[] = array(
            array(
                array('connections' => array('foocon' => array('options' => array('db' => 'val1')))),
                array('connections' => array('foocon' => array('options' => array('replicaSet' => 'val2')))),
            ),
            array('connections' => array(
                'foocon' => array('options' => array('replicaSet' => 'val2')),
            )),
        );

        // connection option readPreferenceTags are merged non-recursively.
        $cases[] = array(
            array(
                array('connections' => array('foocon' => array('options' => array('readPreferenceTags' => array(array('dc' => 'east', 'use' => 'reporting')))))),
                array('connections' => array('foocon' => array('options' => array('readPreferenceTags' => array(array('dc' => 'west'), array()))))),
            ),
            array('connections' => array(
                'foocon' => array('options' => array('readPreferenceTags' => array(array('dc' => 'west'), array()))),
            )),
        );

        // managers are merged non-recursively.
        $cases[] = array(
            array(
                array('document_managers' => array('foodm' => array('database' => 'val1'), 'bardm' => array('database' => 'val2'))),
                array('document_managers' => array('bardm' => array('database' => 'val3'))),
            ),
            array('document_managers' => array(
                'foodm' => array('database' => 'val1', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'default_repository_class' =>  'Doctrine\ODM\MongoDB\DocumentRepository', 'repository_factory' => null, 'filters' => array(), 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
                'bardm' => array('database' => 'val3', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'default_repository_class' =>  'Doctrine\ODM\MongoDB\DocumentRepository', 'repository_factory' => null, 'filters' => array(), 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
            )),
        );

        return $cases;
    }

    /**
     * @dataProvider provideNormalizeOptions
     * @param array $configs  A configuration array to process
     * @param array $expected Array of key/value options expected in the processed configuration
     */
    public function testNormalizeOptions(array $config, array $expected)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($config));

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    public function provideNormalizeOptions()
    {
        $cases = array();

        // connection versus connections (id is the identifier)
        $cases[] = array(
            array('connection' => array(
                array('server' => 'mongodb://abc', 'id' => 'foo'),
                array('server' => 'mongodb://def', 'id' => 'bar'),
            )),
            array('connections' => array(
                'foo' => array('server' => 'mongodb://abc'),
                'bar' => array('server' => 'mongodb://def'),
            )),
        );

        // document_manager versus document_managers (id is the identifier)
        $cases[] = array(
            array('document_manager' => array(
                array('connection' => 'conn1', 'id' => 'foo'),
                array('connection' => 'conn2', 'id' => 'bar'),
            )),
            array('document_managers' => array(
                'foo' => array('connection' => 'conn1', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'default_repository_class' => 'Doctrine\ODM\MongoDB\DocumentRepository', 'repository_factory' => null, 'filters' => array(), 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
                'bar' => array('connection' => 'conn2', 'metadata_cache_driver' => array('type' => 'array'), 'logging' => '%kernel.debug%', 'profiler' => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'), 'auto_mapping' => false, 'default_repository_class' =>  'Doctrine\ODM\MongoDB\DocumentRepository', 'repository_factory' => null,'filters' => array(), 'mappings' => array(), 'retry_connect' => 0, 'retry_query' => 0),
            )),
        );

        // mapping configuration that's beneath a specific document manager
        $cases[] = array(
            array('document_manager' => array(
                array('id' => 'foo', 'connection' => 'conn1', 'mapping' => array(
                    'type' => 'xml', 'name' => 'foo-mapping'
                )),
            )),
            array('document_managers' => array(
                'foo' => array(
                    'connection'   => 'conn1',
                    'metadata_cache_driver' => array('type' => 'array'),
                    'default_repository_class' =>  'Doctrine\ODM\MongoDB\DocumentRepository',
                    'repository_factory' => null,
                    'mappings'     => array('foo-mapping' => array('type' => 'xml', 'mapping' => true)),
                    'logging'      => '%kernel.debug%',
                    'profiler'     => array('enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'),
                    'auto_mapping' => false,
                    'filters'      => array(),
                    'retry_connect' => 0,
                    'retry_query' => 0,
                ),
            )),
        );

        return $cases;
    }

    public function testPasswordAndUsernameShouldBeUnsetIfNull()
    {
        $config = array(
            'connections' => array(
                'conn1' => array(
                    'server' => 'mongodb://localhost',
                    'options' => array(
                        'username' => null,
                        'password' => 'bar',
                    ),
                ),
                'conn2' => array(
                    'server' => 'mongodb://localhost',
                    'options' => array(
                        'username' => 'foo',
                        'password' => null,
                    ),
                ),
                'conn3' => array(
                    'server' => 'mongodb://localhost',
                    'options' => array(
                        'username' => null,
                        'password' => null,
                    ),
                ),
            ),
        );

        $processor = new Processor();
        $configuration = new Configuration(false);
        $options = $processor->processConfiguration($configuration, array($config));

        $this->assertEquals(array('password' => 'bar'), $options['connections']['conn1']['options']);
        $this->assertEquals(array('username' => 'foo'), $options['connections']['conn2']['options']);
        $this->assertEquals(array(), $options['connections']['conn3']['options']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The replicaSet option must be a string
     */
    public function testInvalidReplicaSetValue()
    {
        $config = array(
            'connections' => array(
                'conn1' => array(
                    'server'  => 'mongodb://localhost',
                    'options' => array(
                        'replicaSet' => true
                    )
                )
            )
        );

        $processor = new Processor();
        $configuration = new Configuration(false);
        $processor->processConfiguration($configuration, array($config));
    }

    /**
     * @dataProvider provideExceptionConfiguration
     */
    public function testFixtureLoaderValidation($config)
    {
        $processor = new Processor();
        $configuration = new Configuration(false);
        $this->setExpectedException('\LogicException');
        $processor->processConfiguration($configuration, array($config));
    }

    public function provideExceptionConfiguration()
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__.'/Fixtures/config/yml/exception.yml'));
        $yaml = $yaml['doctrine_mongodb'];

        $xml = XmlUtils::loadFile(__DIR__.'/Fixtures/config/xml/exception.xml');
        $xml = XmlUtils::convertDomElementToArray($xml->getElementsByTagName('config')->item(0));

        return array(
            array($yaml),
            array($xml),
        );
    }
}
