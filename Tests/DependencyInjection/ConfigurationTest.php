<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Configuration;
use Doctrine\ODM\MongoDB\Configuration as ODMConfiguration;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Foo\Bar\CustomGridFSRepository;
use Foo\Bar\CustomRepository;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Yaml\Yaml;
use Vendor\Filter\BasicFilter;
use Vendor\Filter\ComplexFilter;
use Vendor\Filter\DisabledFilter;
use function array_key_exists;
use function file_get_contents;

class ConfigurationTest extends TestCase
{
    public function testDefaults()
    {
        $processor     = new Processor();
        $configuration = new Configuration(false);
        $options       = $processor->processConfiguration($configuration, []);

        $defaults = [
            'fixture_loader'                 => ContainerAwareLoader::class,
            'auto_generate_hydrator_classes' => false,
            'auto_generate_proxy_classes'    => ODMConfiguration::AUTOGENERATE_EVAL,
            'auto_generate_persistent_collection_classes' => ODMConfiguration::AUTOGENERATE_NEVER,
            'default_database'               => 'default',
            'document_managers'              => [],
            'connections'                    => [],
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Proxies',
            'resolve_target_documents'       => [],
            'proxy_namespace'                => 'MongoDBODMProxies',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators',
            'hydrator_namespace'             => 'Hydrators',
            'default_commit_options'         => [],
            'persistent_collection_dir'      => '%kernel.cache_dir%/doctrine/odm/mongodb/PersistentCollections',
            'persistent_collection_namespace'=> 'PersistentCollections',
            'types'                          => [],
        ];

        $this->assertEquals($defaults, $options);
    }

    /**
     * @dataProvider provideFullConfiguration
     */
    public function testFullConfiguration($config)
    {
        $processor     = new Processor();
        $configuration = new Configuration(false);
        $options       = $processor->processConfiguration($configuration, [$config]);

        $expected = [
            'fixture_loader'                 => ContainerAwareLoader::class,
            'auto_generate_hydrator_classes' => 1,
            'auto_generate_proxy_classes'    => ODMConfiguration::AUTOGENERATE_FILE_NOT_EXISTS,
            'auto_generate_persistent_collection_classes' => ODMConfiguration::AUTOGENERATE_EVAL,
            'default_connection'             => 'conn1',
            'default_database'               => 'default_db_name',
            'default_document_manager'       => 'default_dm_name',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Hydrators',
            'hydrator_namespace'             => 'Test_Hydrators',
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Proxies',
            'proxy_namespace'                => 'Test_Proxies',
            'persistent_collection_dir'      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Pcolls',
            'persistent_collection_namespace'=> 'Test_Pcolls',
            'default_commit_options' => [
                'j' => false,
                'timeout' => 10,
                'w' => 'majority',
                'wtimeout' => 10,
                'fsync' => false,
                'safe' => 2,
            ],
            'connections' => [
                'conn1' => [
                    'server'  => 'mongodb://localhost',
                    'options' => [
                        'connectTimeoutMS'  => 500,
                        'db'                => 'database_val',
                        'journal'           => true,
                        'password'          => 'password_val',
                        'readPreference'    => 'secondaryPreferred',
                        'readPreferenceTags' => [
                            ['dc' => 'east', 'use' => 'reporting'],
                            ['dc' => 'west'],
                            [],
                        ],
                        'replicaSet'        => 'foo',
                        'slaveOkay'         => true,
                        'socketTimeoutMS'   => 1000,
                        'ssl'               => true,
                        'authMechanism'     => 'MONGODB-X509',
                        'authSource'        => 'some_db',
                        'username'          => 'username_val',
                        'w'                 => 'majority',
                        'wTimeoutMS'        => 1000,
                    ],
                    'driver_options' => ['context' => 'conn1_context_service'],
                ],
                'conn2' => ['server' => 'mongodb://otherhost'],
            ],
            'document_managers' => [
                'dm1' => [
                    'default_document_repository_class' => DocumentRepository::class,
                    'default_gridfs_repository_class' => DefaultGridFSRepository::class,
                    'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory',
                    'persistent_collection_factory' => null,
                    'logging'      => '%kernel.debug%',
                    'auto_mapping' => false,
                    'filters' => [
                        'disabled_filter' => [
                            'class' => DisabledFilter::class,
                            'enabled' => false,
                            'parameters' => [],
                        ],
                        'basic_filter' => [
                            'class' => BasicFilter::class,
                            'enabled' => true,
                            'parameters' => [],
                        ],
                        'complex_filter' => [
                            'class' => ComplexFilter::class,
                            'enabled' => true,
                            'parameters' => [
                                'integer' => 1,
                                'string' => 'foo',
                                'object' => ['key' => 'value'],
                                'array' => [1, 2, 3],
                            ],
                        ],
                    ],
                    'metadata_cache_driver' => [
                        'type'           => 'memcached',
                        'class'          => 'fooClass',
                        'host'           => 'host_val',
                        'port'           => 1234,
                        'instance_class' => 'instance_val',
                    ],
                    'mappings' => [
                        'FooBundle' => [
                            'type'    => 'annotation',
                            'mapping' => true,
                        ],
                    ],
                    'profiler' => [
                        'enabled' => true,
                        'pretty'  => false,
                    ],
                ],
                'dm2' => [
                    'connection'   => 'dm2_connection',
                    'database'     => 'db1',
                    'logging'      => true,
                    'default_document_repository_class' => CustomRepository::class,
                    'default_gridfs_repository_class' => CustomGridFSRepository::class,
                    'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory',
                    'persistent_collection_factory' => null,
                    'auto_mapping' => false,
                    'filters'      => [],
                    'metadata_cache_driver' => ['type' => 'apc'],
                    'mappings' => [
                        'BarBundle' => [
                            'type'      => 'yml',
                            'dir'       => '%kernel.cache_dir%',
                            'prefix'    => 'prefix_val',
                            'alias'     => 'alias_val',
                            'is_bundle' => false,
                            'mapping'   => true,
                        ],
                    ],
                    'profiler' => [
                        'enabled' => '%kernel.debug%',
                        'pretty'  => '%kernel.debug%',
                    ],
                ],
            ],
            'resolve_target_documents' => ['Foo\BarInterface' => 'Bar\FooClass'],
            'types' => [],
        ];

        $this->assertEquals($expected, $options);
    }

    public function provideFullConfiguration()
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/config/yml/full.yml'));
        $yaml = $yaml['doctrine_mongodb'];

        $xml = XmlUtils::loadFile(__DIR__ . '/Fixtures/config/xml/full.xml');
        $xml = XmlUtils::convertDomElementToArray($xml->getElementsByTagName('config')->item(0));

        return [
            [$yaml],
            [$xml],
        ];
    }

    /**
     * @param array $configs  An array of configuration arrays to process
     * @param array $expected Array of key/value options expected in the processed configuration
     *
     * @dataProvider provideMergeOptions
     */
    public function testMergeOptions(array $configs, array $expected)
    {
        $processor     = new Processor();
        $configuration = new Configuration(false);
        $options       = $processor->processConfiguration($configuration, $configs);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    public function provideMergeOptions()
    {
        $cases = [];

        // single config, testing normal option setting
        $cases[] = [
            [
                ['default_document_manager' => 'foo'],
            ],
            ['default_document_manager' => 'foo'],
        ];

        // single config, testing normal option setting with dashes
        $cases[] = [
            [
                ['default-document-manager' => 'bar'],
            ],
            ['default_document_manager' => 'bar'],
        ];

        // testing the normal override merging - the later config array wins
        $cases[] = [
            [
                ['default_document_manager' => 'foo'],
                ['default_document_manager' => 'baz'],
            ],
            ['default_document_manager' => 'baz'],
        ];

        // the "options" array is totally replaced
        $cases[] = [
            [
                ['connections' => ['default' => ['options' => ['timeout' => 2000]]]],
                ['connections' => ['default' => ['options' => ['username' => 'foo']]]],
            ],
            ['connections' => ['default' => ['options' => ['username' => 'foo']]]],
        ];

        // mappings are merged non-recursively.
        $cases[] = [
            [
                ['document_managers' => ['default' => ['mappings' => ['foomap' => ['type' => 'val1'], 'barmap' => ['dir' => 'val2']]]]],
                ['document_managers' => ['default' => ['mappings' => ['barmap' => ['prefix' => 'val3']]]]],
            ],
            ['document_managers' => ['default' => ['metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => ['foomap' => ['type' => 'val1', 'mapping' => true], 'barmap' => ['prefix' => 'val3', 'mapping' => true]]]]],
        ];

        // connections are merged non-recursively.
        $cases[] = [
            [
                ['connections' => ['foocon' => ['server' => 'val1'], 'barcon' => ['options' => ['username' => 'val2']]]],
                ['connections' => ['barcon' => ['server' => 'val3']]],
            ],
            [
                'connections' => [
                    'foocon' => ['server' => 'val1'],
                    'barcon' => ['server' => 'val3'],
                ],
            ],
        ];

        // connection options are merged non-recursively.
        $cases[] = [
            [
                ['connections' => ['foocon' => ['options' => ['db' => 'val1']]]],
                ['connections' => ['foocon' => ['options' => ['replicaSet' => 'val2']]]],
            ],
            [
                'connections' => [
                    'foocon' => ['options' => ['replicaSet' => 'val2']],
                ],
            ],
        ];

        // connection option readPreferenceTags are merged non-recursively.
        $cases[] = [
            [
                ['connections' => ['foocon' => ['options' => ['readPreferenceTags' => [['dc' => 'east', 'use' => 'reporting']]]]]],
                ['connections' => ['foocon' => ['options' => ['readPreferenceTags' => [['dc' => 'west'], []]]]]],
            ],
            [
                'connections' => [
                    'foocon' => ['options' => ['readPreferenceTags' => [['dc' => 'west'], []]]],
                ],
            ],
        ];

        // managers are merged non-recursively.
        $cases[] = [
            [
                ['document_managers' => ['foodm' => ['database' => 'val1'], 'bardm' => ['database' => 'val2']]],
                ['document_managers' => ['bardm' => ['database' => 'val3']]],
            ],
            [
                'document_managers' => [
                    'foodm' => ['database' => 'val1', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => []],
                    'bardm' => ['database' => 'val3', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => []],
                ],
            ],
        ];

        return $cases;
    }

    /**
     * @param array $configs  A configuration array to process
     * @param array $expected Array of key/value options expected in the processed configuration
     *
     * @dataProvider provideNormalizeOptions
     */
    public function testNormalizeOptions(array $config, array $expected)
    {
        $processor     = new Processor();
        $configuration = new Configuration(false);
        $options       = $processor->processConfiguration($configuration, [$config]);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    public function provideNormalizeOptions()
    {
        $cases = [];

        // connection versus connections (id is the identifier)
        $cases[] = [
            [
                'connection' => [
                    ['server' => 'mongodb://abc', 'id' => 'foo'],
                    ['server' => 'mongodb://def', 'id' => 'bar'],
                ],
            ],
            [
                'connections' => [
                    'foo' => ['server' => 'mongodb://abc'],
                    'bar' => ['server' => 'mongodb://def'],
                ],
            ],
        ];

        // document_manager versus document_managers (id is the identifier)
        $cases[] = [
            [
                'document_manager' => [
                    ['connection' => 'conn1', 'id' => 'foo'],
                    ['connection' => 'conn2', 'id' => 'bar'],
                ],
            ],
            [
                'document_managers' => [
                    'foo' => ['connection' => 'conn1', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => []],
                    'bar' => ['connection' => 'conn2', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null,'filters' => [], 'mappings' => []],
                ],
            ],
        ];

        // mapping configuration that's beneath a specific document manager
        $cases[] = [
            [
                'document_manager' => [
                    [
                        'id' => 'foo',
                        'connection' => 'conn1',
                        'mapping' => [
                            'type' => 'xml',
                            'name' => 'foo-mapping',
                        ],
                    ],
                ],
            ],
            [
                'document_managers' => [
                    'foo' => [
                        'connection'   => 'conn1',
                        'metadata_cache_driver' => ['type' => 'array'],
                        'default_document_repository_class' =>  DocumentRepository::class,
                        'default_gridfs_repository_class' => DefaultGridFSRepository::class,
                        'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory',
                        'persistent_collection_factory' => null,
                        'mappings'     => ['foo-mapping' => ['type' => 'xml', 'mapping' => true]],
                        'logging'      => '%kernel.debug%',
                        'profiler'     => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'],
                        'auto_mapping' => false,
                        'filters'      => [],
                    ],
                ],
            ],
        ];

        return $cases;
    }

    public function testPasswordAndUsernameShouldBeUnsetIfNull()
    {
        $config = [
            'connections' => [
                'conn1' => [
                    'server' => 'mongodb://localhost',
                    'options' => [
                        'username' => null,
                        'password' => 'bar',
                    ],
                ],
                'conn2' => [
                    'server' => 'mongodb://localhost',
                    'options' => [
                        'username' => 'foo',
                        'password' => null,
                    ],
                ],
                'conn3' => [
                    'server' => 'mongodb://localhost',
                    'options' => [
                        'username' => null,
                        'password' => null,
                    ],
                ],
            ],
        ];

        $processor     = new Processor();
        $configuration = new Configuration(false);
        $options       = $processor->processConfiguration($configuration, [$config]);

        $this->assertEquals(['password' => 'bar'], $options['connections']['conn1']['options']);
        $this->assertEquals(['username' => 'foo'], $options['connections']['conn2']['options']);
        $this->assertEquals([], $options['connections']['conn3']['options']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The replicaSet option must be a string
     */
    public function testInvalidReplicaSetValue()
    {
        $config = [
            'connections' => [
                'conn1' => [
                    'server'  => 'mongodb://localhost',
                    'options' => ['replicaSet' => true],
                ],
            ],
        ];

        $processor     = new Processor();
        $configuration = new Configuration(false);
        $processor->processConfiguration($configuration, [$config]);
    }

    public function testNullReplicaSetValue()
    {
        $config = [
            'connections' => [
                'conn1' => [
                    'server'  => 'mongodb://localhost',
                    'options' => ['replicaSet' => null],
                ],
            ],
        ];

        $processor       = new Processor();
        $configuration   = new Configuration(false);
        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertFalse(array_key_exists('replicaSet', $processedConfig['connections']['conn1']['options']));
    }

    /**
     * @dataProvider provideExceptionConfiguration
     */
    public function testFixtureLoaderValidation($config)
    {
        $processor     = new Processor();
        $configuration = new Configuration(false);
        $this->expectException(LogicException::class);
        $processor->processConfiguration($configuration, [$config]);
    }

    public function provideExceptionConfiguration()
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/config/yml/exception.yml'));
        $yaml = $yaml['doctrine_mongodb'];

        $xml = XmlUtils::loadFile(__DIR__ . '/Fixtures/config/xml/exception.xml');
        $xml = XmlUtils::convertDomElementToArray($xml->getElementsByTagName('config')->item(0));

        return [
            [$yaml],
            [$xml],
        ];
    }
}
