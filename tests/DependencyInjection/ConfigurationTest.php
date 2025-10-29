<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Configuration;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\BasicFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\ComplexFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\DisabledFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Repository\CustomGridFSRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Repository\CustomRepository;
use Doctrine\ODM\MongoDB\Configuration as ODMConfiguration;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;
use function array_merge;
use function file_get_contents;
use function method_exists;

use const PHP_VERSION_ID;

class ConfigurationTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testDefaults(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, []);

        $defaults = [
            'auto_generate_hydrator_classes' => false,
            'auto_generate_proxy_classes'    => ODMConfiguration::AUTOGENERATE_EVAL,
            'auto_generate_persistent_collection_classes' => ODMConfiguration::AUTOGENERATE_NEVER,
            'enable_lazy_ghost_objects'      => method_exists(ODMConfiguration::class, 'setUseLazyGhostObject'),
            'enable_native_lazy_objects'     => PHP_VERSION_ID >= 80400 && method_exists(ODMConfiguration::class, 'setUseNativeLazyObject'),
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
            'persistent_collection_namespace' => 'PersistentCollections',
            'types'                          => [],
            'controller_resolver'            => [
                'enabled'      => true,
                'auto_mapping' => true,
            ],
        ];

        $this->assertEquals($defaults, $options);
    }

    #[Group('legacy')]
    #[DataProvider('provideFullConfiguration')]
    public function testFullConfiguration(array $config): void
    {
        self::expectDeprecation('Since doctrine/mongodb-odm-bundle 5.4: The "context" driver option is deprecated and will be removed in 3.0. This option is ignored by the MongoDB driver version 2.');

        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, [$config]);

        $expected = [
            'auto_generate_hydrator_classes' => 1,
            'auto_generate_proxy_classes'    => ODMConfiguration::AUTOGENERATE_FILE_NOT_EXISTS,
            'auto_generate_persistent_collection_classes' => ODMConfiguration::AUTOGENERATE_EVAL,
            'enable_native_lazy_objects'     => false,
            'enable_lazy_ghost_objects'      => false,
            'default_connection'             => 'conn1',
            'default_database'               => 'default_db_name',
            'default_document_manager'       => 'default_dm_name',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Hydrators',
            'hydrator_namespace'             => 'Test_Hydrators',
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Proxies',
            'proxy_namespace'                => 'Test_Proxies',
            'persistent_collection_dir'      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Pcolls',
            'persistent_collection_namespace' => 'Test_Pcolls',
            'default_commit_options' => [
                'j' => false,
                'timeout' => 10,
                'w' => 'majority',
                'wtimeout' => 10,
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
                        'replicaSet'                           => 'foo',
                        'socketTimeoutMS'                      => 1000,
                        'ssl'                                  => true,
                        'tls'                                  => true,
                        'tlsAllowInvalidCertificates'          => false,
                        'tlsAllowInvalidHostnames'             => false,
                        'tlsCAFile'                            => '/path/to/cert.pem',
                        'tlsCertificateKeyFile'                => '/path/to/key.crt',
                        'tlsCertificateKeyFilePassword'        => 'secret',
                        'tlsDisableCertificateRevocationCheck' => false,
                        'tlsDisableOCSPEndpointCheck'          => false,
                        'tlsInsecure'                          => false,
                        'authMechanism'                        => 'MONGODB-X509',
                        'authSource'                           => 'some_db',
                        'username'                             => 'username_val',
                        'retryReads'                           => false,
                        'retryWrites'                          => false,
                        'w'                                    => 'majority',
                        'wTimeoutMS'                           => 1000,
                    ],
                    'driver_options' => ['context' => 'conn1_context_service'],
                    'autoEncryption' => [
                        'kmsProvider' => [
                            'type' => 'aws',
                            'accessKeyId' => 'MONGODB_AWS_ACCESS_KEY_ID',
                            'secretAccessKey' => 'MONGODB_AWS_SECRET_ACCESS_KEY',
                            'sessionToken' => 'MONGODB_AWS_SESSION_TOKEN',
                        ],
                        'masterKey' => ['key' => 'MONGODB_AWS_MASTER_KEY'],
                        'keyVaultClient' => 'my_key_vault_client_service',
                        'keyVaultNamespace' => 'encryption.__keyVault',
                        'tlsOptions' => [
                            'tlsCAFile' => '%kernel.project_dir%/config/certificates/mongodb-ca.pem',
                            'tlsCertificateKeyFile' => '%kernel.project_dir%/config/certificates/mongodb-client.pem',
                            'tlsCertificateKeyFilePassword' => 'MONGODB_TLS_CERTIFICATE_KEY_FILE_PASSWORD',
                            'tlsDisableOCSPEndpointCheck' => false,
                        ],
                        'bypassAutoEncryption' => true,
                        'bypassQueryAnalysis' => true,
                        'encryptedFieldsMap' => [
                            'encrypted.RangeTypes' => [
                                'fields' => [
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'lhZHItpvRkqXevh4Wtqg/g==', 'subType' => '04']],
                                        'path' => 'intField',
                                        'bsonType' => 'int',
                                        'queries' => ['queryType' => 'range', 'contention' => 8, 'min' => 5, 'max' => 10],
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'qd9PEKIPTE2J30ev29lMpQ==', 'subType' => '04']],
                                        'path' => 'floatField',
                                        'bsonType' => 'double',
                                        'queries' => ['queryType' => 'range', 'contention' => 8, 'min' => 5.5, 'max' => 10.5, 'precision' => 1],
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'zVLg8CF4RSSu4xn7x7dOyQ==', 'subType' => '04']],
                                        'path' => 'decimalField',
                                        'bsonType' => 'decimal',
                                        'queries' => [
                                            'queryType' => 'range',
                                            'contention' => 8,
                                            'min' => ['$numberDecimal' => '0.1'],
                                            'max' => ['$numberDecimal' => '0.2'],
                                            'precision' => 2,
                                        ],
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'ySdd8lZ2QBqnwKPJTp/yLA==', 'subType' => '04']],
                                        'path' => 'immutableDateField',
                                        'bsonType' => 'date',
                                        'queries' => [
                                            'queryType' => 'range',
                                            'contention' => 8,
                                            'min' => ['$date' => '2000-01-01T00:00:00Z'],
                                            'max' => ['$date' => '2100-01-01T00:00:00Z'],
                                        ],
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'NWKI+DyES/OlNkUbJbWJ9w==', 'subType' => '04']],
                                        'path' => 'dateField',
                                        'bsonType' => 'date',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'wiiv+0K/QAquyEq3HDxRKw==', 'subType' => '04']],
                                        'path' => 'binField',
                                        'bsonType' => 'binData',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => '2CSosXLSTEKaYphcSnUuCw==', 'subType' => '04']],
                                        'path' => 'timestampField',
                                        'bsonType' => 'timestamp',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'h3H6HdG3T5CK+Z2yQ4Ho+Q==', 'subType' => '04']],
                                        'path' => 'hashField',
                                        'bsonType' => 'object',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'X78UZZ/HTX2wLw4K3uG42w==', 'subType' => '04']],
                                        'path' => 'collectionField',
                                        'bsonType' => 'objectId',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'LugQL/ZXTJOl856Yacmkwg==', 'subType' => '04']],
                                        'path' => 'boolField',
                                        'bsonType' => 'bool',
                                    ],
                                ],
                            ],
                            'encrypted.patients' => [
                                'fields' => [
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'GH25/XvYSaCgTUQLAo1hQw==', 'subType' => '04']],
                                        'path' => 'pathologies',
                                        'bsonType' => 'array',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'krVWyFlNTUOaGFMfk+s7UA==', 'subType' => '04']],
                                        'path' => 'patientRecord.billing',
                                        'bsonType' => 'object',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'X1ZaSI1GSAKnZ+sPGcmYBA==', 'subType' => '04']],
                                        'path' => 'patientRecord.billingAmount',
                                        'bsonType' => 'int',
                                        'queries' => [
                                            'queryType' => 'range',
                                            'contention' => 8,
                                            'min' => 100,
                                            'max' => 2000,
                                            'sparsity' => 1,
                                            'trimFactor' => 4,
                                        ],
                                    ],
                                ],
                            ],
                            'encrypted.client' => [
                                'fields' => [
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'I0Aw18vnRGWzVS1t3uejpQ==', 'subType' => '04']],
                                        'path' => 'name',
                                        'bsonType' => 'string',
                                    ],
                                    [
                                        'keyId' => ['$binary' => ['base64' => 'XSPRK3vaTLmMZr9IEj/qwQ==', 'subType' => '04']],
                                        'path' => 'clientCards',
                                        'bsonType' => 'array',
                                    ],
                                ],
                            ],
                        ],
                        'extraOptions' => [
                            'mongocryptdURI' => 'mongodb://localhost:27020',
                            'mongocryptdBypassSpawn' => true,
                            'mongocryptdSpawnPath' => '%kernel.project_dir%/bin/mongocryptd',
                            'mongocryptdSpawnArgs' => ['--pidfilepath=%kernel.project_dir%/var/mongocryptd.pid', '--idleShutdownTimeoutSecs=60'],
                            'cryptSharedLibPath' => '%kernel.project_dir%/bin/mongo_crypt_v1.dylib',
                            'cryptSharedLibRequired' => true,
                        ],
                    ],
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
                            'type'    => 'attribute',
                            'mapping' => true,
                        ],
                    ],
                    'profiler' => [
                        'enabled' => true,
                        'pretty'  => false,
                    ],
                    'use_transactional_flush' => false,
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
                    'metadata_cache_driver' => ['type' => 'apcu'],
                    'mappings' => [
                        'BarBundle' => [
                            'type'      => 'xml',
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
                    'use_transactional_flush' => false,
                ],
            ],
            'resolve_target_documents' => ['Foo\BarInterface' => 'Bar\FooClass'],
            'types' => [],
            'controller_resolver' => [
                'enabled'      => true,
                'auto_mapping' => true,
            ],
        ];

        $this->assertEquals($expected, $options);
    }

    /** @return array<mixed[]> */
    public static function provideFullConfiguration(): array
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/config/yml/full.yml'));
        $yaml = $yaml['doctrine_mongodb'];

        $xml = XmlUtils::loadFile(__DIR__ . '/Fixtures/config/xml/full.xml');
        $xml = XmlUtils::convertDomElementToArray($xml->getElementsByTagName('config')->item(0));

        return [
            'yaml' => [$yaml],
            'xml' => [$xml],
        ];
    }

    /**
     * @param array $configs  An array of configuration arrays to process
     * @param array $expected Array of key/value options expected in the processed configuration
     */
    #[DataProvider('provideMergeOptions')]
    public function testMergeOptions(array $configs, array $expected): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, $configs);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    /** @return array<mixed[]> */
    public static function provideMergeOptions(): array
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
                ['connections' => ['default' => ['options' => ['socketTimeoutMS' => 2000]]]],
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
            ['document_managers' => ['default' => ['metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => ['foomap' => ['type' => 'val1', 'mapping' => true], 'barmap' => ['prefix' => 'val3', 'mapping' => true]], 'use_transactional_flush' => false]]],
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
                    'foodm' => ['database' => 'val1', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                    'bardm' => ['database' => 'val3', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                ],
            ],
        ];

        return $cases;
    }

    /**
     * @param array $config   A configuration array to process
     * @param array $expected Array of key/value options expected in the processed configuration
     */
    #[DataProvider('provideNormalizeOptions')]
    public function testNormalizeOptions(array $config, array $expected): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, [$config]);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    /** @return Generator<array{0: array<string, mixed>, 1: array<string, mixed>}> */
    public static function provideNormalizeOptions(): Generator
    {
        // connection versus connections (id is the identifier)
        yield [
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
        yield [
            [
                'document_manager' => [
                    ['connection' => 'conn1', 'id' => 'foo'],
                    ['connection' => 'conn2', 'id' => 'bar'],
                ],
            ],
            [
                'document_managers' => [
                    'foo' => ['connection' => 'conn1', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                    'bar' => ['connection' => 'conn2', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null,'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                ],
            ],
        ];

        // mapping configuration that's beneath a specific document manager
        yield [
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
                        'use_transactional_flush' => false,
                    ],
                ],
            ],
        ];

        // Encrypted Field Map can be a JSON string in a <![CDATA[...]]>
        yield [
            [
                'connection' => [
                    [
                        'server' => 'mongodb://abc',
                        'id' => 'foo',
                        'autoEncryption' => [
                            'kmsProvider' => ['type' => 'local', 'key' => '1234567890123456789012345678901234567890123456789012345678901234'],
                            'encryptedFieldsMap' => <<<'JSON'
                            {
                                "encrypted.patients": {
                                    "fields": [
                                        {
                                            "keyId": { "$binary": { "base64": "GH25/XvYSaCgTUQLAo1hQw==", "subType": "04" } },
                                            "path": "pathologies",
                                            "bsonType": "array"
                                        },
                                        {
                                            "keyId": { "$binary": { "base64": "krVWyFlNTUOaGFMfk+s7UA==", "subType": "04" } },
                                            "path": "patientRecord.billing",
                                            "bsonType": "object"
                                        },
                                        {
                                            "keyId": { "$binary": { "base64": "X1ZaSI1GSAKnZ+sPGcmYBA==", "subType": "04" } },
                                            "path": "patientRecord.billingAmount",
                                            "bsonType": "int",
                                            "queries": { "queryType": "range", "contention": 8, "min": 100, "max": 2000, "sparsity": 1, "trimFactor": 4 }
                                        }
                                    ]
                                },
                                "encrypted.client": {
                                    "fields": [
                                        {
                                            "keyId": { "$binary": { "base64": "I0Aw18vnRGWzVS1t3uejpQ==", "subType": "04" } },
                                            "path": "name",
                                            "bsonType": "string"
                                        },
                                        {
                                            "keyId": { "$binary": { "base64": "XSPRK3vaTLmMZr9IEj/qwQ==", "subType": "04" } },
                                            "path": "clientCards",
                                            "bsonType": "array"
                                        }
                                    ]
                                }
                            }
                            JSON,
                        ],
                    ],
                ],
            ],
            [
                'connections' => [
                    'foo' => [
                        'server' => 'mongodb://abc',
                        'autoEncryption' => [
                            'kmsProvider' => ['type' => 'local', 'key' => '1234567890123456789012345678901234567890123456789012345678901234'],
                            'encryptedFieldsMap' => [
                                'encrypted.patients' => [
                                    'fields' => [
                                        [
                                            'keyId' => ['$binary' => ['base64' => 'GH25/XvYSaCgTUQLAo1hQw==', 'subType' => '04']],
                                            'path' => 'pathologies',
                                            'bsonType' => 'array',
                                        ],
                                        [
                                            'keyId' => ['$binary' => ['base64' => 'krVWyFlNTUOaGFMfk+s7UA==', 'subType' => '04']],
                                            'path' => 'patientRecord.billing',
                                            'bsonType' => 'object',
                                        ],
                                        [
                                            'keyId' => ['$binary' => ['base64' => 'X1ZaSI1GSAKnZ+sPGcmYBA==', 'subType' => '04']],
                                            'path' => 'patientRecord.billingAmount',
                                            'bsonType' => 'int',
                                            'queries' => [
                                                'queryType' => 'range',
                                                'contention' => 8,
                                                'min' => 100,
                                                'max' => 2000,
                                                'sparsity' => 1,
                                                'trimFactor' => 4,
                                            ],
                                        ],
                                    ],
                                ],
                                'encrypted.client' => [
                                    'fields' => [
                                        [
                                            'keyId' => ['$binary' => ['base64' => 'I0Aw18vnRGWzVS1t3uejpQ==', 'subType' => '04']],
                                            'path' => 'name',
                                            'bsonType' => 'string',
                                        ],
                                        [
                                            'keyId' => ['$binary' => ['base64' => 'XSPRK3vaTLmMZr9IEj/qwQ==', 'subType' => '04']],
                                            'path' => 'clientCards',
                                            'bsonType' => 'array',
                                        ],
                                    ],
                                ],
                            ],
                        ],

                    ],
                ],
            ],
        ];
    }

    public function testPasswordAndUsernameShouldBeUnsetIfNull(): void
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
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, [$config]);

        $this->assertEquals(['password' => 'bar'], $options['connections']['conn1']['options']);
        $this->assertEquals(['username' => 'foo'], $options['connections']['conn2']['options']);
        $this->assertEquals([], $options['connections']['conn3']['options']);
    }

    public function testInvalidReplicaSetValue(): void
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
        $configuration = new Configuration();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The replicaSet option must be a string');

        $processor->processConfiguration($configuration, [$config]);
    }

    public function testNullReplicaSetValue(): void
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
        $configuration   = new Configuration();
        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertFalse(array_key_exists('replicaSet', $processedConfig['connections']['conn1']['options']));
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function processConfiguration(array $config): array
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration($configuration, [$this->getMinimalValidConfig($config)]);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function getMinimalValidConfig(array $config = []): array
    {
        $baseConfig = [
            'connections' => [
                'default' => [
                    'driver_options' => [], // Placeholder for autoEncryption or other options
                ],
            ],
            'document_managers' => [
                'default' => [],
            ],
        ];

        // Deep merge config into baseConfig
        if (isset($config['connections']['default']['driver_options'])) {
            $baseConfig['connections']['default']['driver_options'] = array_merge(
                $baseConfig['connections']['default']['driver_options'],
                $config['connections']['default']['driver_options'],
            );
            unset($config['connections']['default']['driver_options']);
        }

        if (isset($config['connections']['default'])) {
            $baseConfig['connections']['default'] = array_merge(
                $baseConfig['connections']['default'],
                $config['connections']['default'],
            );
            unset($config['connections']['default']);
        }

        if (isset($config['connections'])) {
            $baseConfig['connections'] = array_merge(
                $baseConfig['connections'],
                $config['connections'],
            );
            unset($config['connections']);
        }

        return array_merge($baseConfig, $config);
    }
}
