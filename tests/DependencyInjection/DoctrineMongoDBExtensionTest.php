<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Closure;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\Bundle\MongoDBBundle\Command\Encryption\DiagnosticCommand;
use Doctrine\Bundle\MongoDBBundle\Command\Encryption\DumpFieldsMapCommand;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\AttributesBundle\Document\TestDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\DocumentListenerBundle\EventListener\TestAttributeListener;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations;
use InvalidArgumentException;
use MongoDB\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use stdClass;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\ResolveParameterPlaceHoldersPass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\VarExporter\LazyGhostTrait;

use function array_diff_key;
use function array_merge;
use function interface_exists;
use function is_dir;
use function method_exists;
use function sprintf;
use function sys_get_temp_dir;
use function trait_exists;

use const PHP_VERSION_ID;

/**
 * @phpstan-type ConfigurationArray array{
 *     enable_lazy_ghost_objects?: bool,
 *     enable_native_lazy_objects?: bool,
 * }
 */
class DoctrineMongoDBExtensionTest extends TestCase
{
    public static function buildConfiguration(array $settings = []): array
    {
        return [
            array_merge(
                [
                    'connections' => ['default' => []],
                    'document_managers' => ['default' => []],
                ],
                $settings,
            ),
        ];
    }

    public function buildMinimalContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.root_dir'         => __DIR__,
            'kernel.project_dir'      => __DIR__,
            'kernel.cache_dir'        => sys_get_temp_dir() . '/doctrine_mongodb_odm_bundle',
            'kernel.name'             => 'kernel',
            'kernel.environment'      => 'test',
            'kernel.debug'            => 'true',
            'kernel.bundles'          => [],
            'kernel.bundles_metadata' => [],
            'kernel.container_class'  => Container::class,
        ]));
    }

    #[DataProvider('parameterProvider')]
    public function testParameterOverride(string $option, string $parameter, string $value): void
    {
        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration([$option => $value]), $container);

        $this->assertEquals($value, $container->getParameter('doctrine_mongodb.odm.' . $parameter));
    }

    public function testAsDocumentListenerAttribute(): void
    {
        $container = $this->getContainer('DocumentListenerBundle');
        $extension = new DoctrineMongoDBExtension();
        $container->registerExtension($extension);

        $extension->load([
            [
                'connections' => ['default' => []],
                'document_managers' => [
                    'default' => [
                        'mappings' => ['DocumentListenerBundle' => 'attribute'],
                    ],
                ],
            ],
        ], $container);

        $container->register(TestAttributeListener::class, TestAttributeListener::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $container->setAlias('test_alias__' . TestAttributeListener::class, new Alias(TestAttributeListener::class, true));
        $container->compile();

        $listenerDefinition = $container->getDefinition('test_alias__' . TestAttributeListener::class);

        self::assertSame([
            [
                'event' => 'prePersist',
                'connection' => 'test',
                'priority' => 10,
            ],
        ], $listenerDefinition->getTag('doctrine_mongodb.odm.event_listener'));
    }

    /** @return array<array{0: class-string}> */
    public static function provideAttributeExcludedFromContainer(): array
    {
        return [
            'Document' => [Annotations\Document::class],
            'EmbeddedDocument' => [Annotations\EmbeddedDocument::class],
            'MappedSuperclass' => [Annotations\MappedSuperclass::class],
            'View' => [Annotations\View::class],
            'QueryResultDocument' => [Annotations\QueryResultDocument::class],
            'File' => [Annotations\File::class],
        ];
    }

    #[DataProvider('provideAttributeExcludedFromContainer')]
    public function testDocumentAttributeExcludesFromContainer(string $class): void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMongoDBExtension();
        $extension->load($this->buildConfiguration(), $container);

        // @phpstan-ignore function.alreadyNarrowedType
        if (method_exists($container, 'getAttributeAutoconfigurators')) {
            // Compatibility with Symfony 7.3 and later
            $autoconfigurator = $container->getAttributeAutoconfigurators()[$class][0];
        } else {
            // Compatibility with Symfony 7.2 and earlier
            $autoconfigurator = $container->getAutoconfiguredAttributes()[$class];
        }

        $this->assertInstanceOf(Closure::class, $autoconfigurator);

        $definition = new ChildDefinition('');
        $autoconfigurator($definition);

        $this->assertSame([['source' => sprintf('with #[%s] attribute', $class)]], $definition->getTag('container.excluded'));
    }

    /** @param string|string[] $bundles */
    private function getContainer(string|array $bundles = 'OtherXmlBundle'): ContainerBuilder
    {
        $bundles = (array) $bundles;

        $map         = [];
        $metadataMap = [];
        foreach ($bundles as $bundle) {
            $bundleDir = __DIR__ . '/Fixtures/Bundles/' . $bundle;

            if (is_dir($bundleDir . '/src')) {
                require_once $bundleDir . '/src/' . $bundle . '.php';
            } else {
                require_once $bundleDir . '/' . $bundle . '.php';
            }

            $map[$bundle] = 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\' . $bundle . '\\' . $bundle;

            $metadataMap[$bundle] = [
                'path' => $bundleDir,
                'namespace' => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\' . $bundle,
            ];
        }

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'            => false,
            'kernel.bundles'          => $map,
            'kernel.bundles_metadata' => $metadataMap,
            'kernel.cache_dir'        => sys_get_temp_dir(),
            'kernel.environment'      => 'test',
            'kernel.name'             => 'kernel',
            'kernel.root_dir'         => __DIR__ . '/../../',
            'kernel.project_dir'      => __DIR__ . '/../../',
            'kernel.container_class'  => Container::class,
        ]));
    }

    public static function parameterProvider(): array
    {
        return [
            ['proxy_namespace', 'proxy_namespace', 'foo'],
            ['proxy-namespace', 'proxy_namespace', 'bar'],
        ];
    }

    public static function getAutomappingConfigurations(): array
    {
        return [
            [
                [
                    'dm1' => [
                        'connection' => 'cn1',
                        'mappings' => ['OtherXmlBundle' => null],
                    ],
                    'dm2' => [
                        'connection' => 'cn2',
                        'mappings' => ['XmlBundle' => null],
                    ],
                    'dm3' => [
                        'connection' => 'cn3',
                        'mappings' => ['NewXmlBundle' => null],
                    ],
                ],
            ],
            [
                [
                    'dm1' => [
                        'connection' => 'cn1',
                        'auto_mapping' => true,
                    ],
                    'dm2' => [
                        'connection' => 'cn2',
                        'mappings' => ['XmlBundle' => null],
                    ],
                    'dm3' => [
                        'connection' => 'cn2',
                        'mappings' => ['NewXmlBundle' => null],
                    ],
                ],
            ],
            [
                [
                    'dm1' => [
                        'connection' => 'cn1',
                        'auto_mapping' => true,
                        'mappings' => ['OtherXmlBundle' => null],
                    ],
                    'dm2' => [
                        'connection' => 'cn2',
                        'mappings' => ['XmlBundle' => null],
                    ],
                    'dm3' => [
                        'connection' => 'cn2',
                        'mappings' => ['NewXmlBundle' => null],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('getAutomappingConfigurations')]
    public function testAutomapping(array $documentManagers): void
    {
        $container = $this->getContainer([
            'OtherXmlBundle',
            'XmlBundle',
            'NewXmlBundle',
        ]);

        $loader = new DoctrineMongoDBExtension();

        $loader->load(
            [
                [
                    'default_database' => 'test_database',
                    'connections' => [
                        'cn1' => [],
                        'cn2' => [],
                        'cn3' => [],
                    ],
                    'document_managers' => $documentManagers,
                ],
            ],
            $container,
        );

        $configDm1 = $container->getDefinition('doctrine_mongodb.odm.dm1_configuration');
        $configDm2 = $container->getDefinition('doctrine_mongodb.odm.dm2_configuration');
        $configDm3 = $container->getDefinition('doctrine_mongodb.odm.dm3_configuration');

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    ['OtherXmlBundle' => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\OtherXmlBundle\Document'],
                ],
            ],
            $configDm1->getMethodCalls(),
        );

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    ['XmlBundle' => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document'],
                ],
            ],
            $configDm2->getMethodCalls(),
        );

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    ['NewXmlBundle' => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\NewXmlBundle\Document'],
                ],
            ],
            $configDm3->getMethodCalls(),
        );
    }

    public function testFactoriesAreRegistered(): void
    {
        $container = $this->getContainer();

        $loader = new DoctrineMongoDBExtension();
        $loader->load(
            [
                [
                    'default_database' => 'test_database',
                    'connections' => [
                        'cn1' => [],
                        'cn2' => [],
                    ],
                    'document_managers' => [
                        'dm1' => [
                            'connection' => 'cn1',
                            'repository_factory' => 'repository_factory_service',
                            'persistent_collection_factory' => 'persistent_collection_factory_service',
                        ],
                    ],
                ],
            ],
            $container,
        );

        $configDm1 = $container->getDefinition('doctrine_mongodb.odm.dm1_configuration');

        $this->assertDICDefinitionMethodCall($configDm1, 'setRepositoryFactory', [
            new Reference('repository_factory_service'),
        ]);

        $this->assertDICDefinitionMethodCall($configDm1, 'setPersistentCollectionFactory', [
            new Reference('persistent_collection_factory_service'),
        ]);
    }

    public function testPublicServicesAndAliases(): void
    {
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $this->assertTrue($container->getDefinition('doctrine_mongodb')->isPublic());
        $this->assertTrue($container->getDefinition('doctrine_mongodb.odm.default_document_manager')->isPublic());
        $this->assertTrue($container->getAlias('doctrine_mongodb.odm.document_manager')->isPublic());
    }

    public function testMessengerIntegration(): void
    {
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $subscriber = $container->getDefinition('doctrine_mongodb.messenger.event_subscriber.doctrine_clear_document_manager');
        $this->assertCount(1, $subscriber->getArguments());
    }

    private function assertDICDefinitionMethodCall(Definition $definition, string $methodName, array $params = []): void
    {
        $calls = $definition->getMethodCalls();

        foreach ($calls as $call) {
            if ($call[0] !== $methodName) {
                continue;
            }

            $this->assertEquals($params, $call[1], "Expected parameters to methods '" . $methodName . "' do not match the actual parameters.");

            return;
        }

        $this->fail("Method '" . $methodName . "' is expected to be called once, definition does not contain a call though.");
    }

    /** @requires function \Symfony\Bridge\Doctrine\ArgumentResolver\EntityValueResolver::__construct */
    public function testControllerResolver(): void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMongoDBExtension();
        $extension->load(self::buildConfiguration(), $container);

        $controllerResolver = $container->getDefinition('doctrine_mongodb.odm.entity_value_resolver');

        $this->assertEquals([
            new Reference('doctrine_mongodb'),
            new Reference('doctrine_mongodb.odm.document_value_resolver.expression_language', $container::IGNORE_ON_INVALID_REFERENCE),
        ], $controllerResolver->getArguments());

        $container = $this->getContainer();

        $extension->load(self::buildConfiguration([
            'controller_resolver' => [
                'enabled' => false,
                'auto_mapping' => false,
            ],
        ]), $container);

        $container->setDefinition('controller_resolver_defaults', $container->getDefinition('doctrine_mongodb.odm.entity_value_resolver')->getArgument(2))->setPublic(true);
        $container->compile();
        $this->assertEquals(new MapDocument(null, null, null, [], null, null, null, true), $container->get('controller_resolver_defaults'));
    }

    public function testTransactionalFlushConfigurationWhenNotSupported(): void
    {
        if (InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.7@dev')) {
            $this->markTestSkipped('Installed version of doctrine/mongodb-odm supports transactional flushes');
        }

        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(['document_managers' => ['default' => ['use_transactional_flush' => true]]]), $container);

        $configuration = $container->getDefinition('doctrine_mongodb.odm.default_configuration');

        $this->assertFalse($configuration->hasMethodCall('setUseTransactionalFlush'), 'setUseTransactionalFlush is not called');
    }

    public function testDefaultTransactionalFlush(): void
    {
        if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.7@dev')) {
            $this->markTestSkipped('Installed version of doctrine/mongodb-odm does not support transactional flushes');
        }

        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container);

        $configuration = $container->getDefinition('doctrine_mongodb.odm.default_configuration');

        $this->assertContains(
            [
                'setUseTransactionalFlush',
                [false],
            ],
            $configuration->getMethodCalls(),
        );
    }

    public function testUseTransactionalFlush(): void
    {
        if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.7@dev')) {
            $this->markTestSkipped('Installed version of doctrine/mongodb-odm does not support transactional flushes');
        }

        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(['document_managers' => ['default' => ['use_transactional_flush' => true]]]), $container);

        $configuration = $container->getDefinition('doctrine_mongodb.odm.default_configuration');

        $this->assertContains(
            [
                'setUseTransactionalFlush',
                [true],
            ],
            $configuration->getMethodCalls(),
        );
    }

    public function testEncryptionCommands(): void
    {
        self::requireAutoEncryptionSupportInODM();

        $container = $this->buildMinimalContainer();
        $loader    = new DoctrineMongoDBExtension();

        $config = [
            'connections' => [
                'default' => [
                    'autoEncryption' => [
                        'kmsProvider' => ['type' => 'local', 'key' => 'base64_encoded_key'],
                    ],
                ],
            ],
            'document_managers' => ['default' => []],
        ];

        $loader->load([$config], $container);
        (new ServiceRepositoryCompilerPass())->process($container);

        $dumpFieldsMapCommand = $container->get('doctrine_mongodb.odm.command.encryption_dump_fields_map');
        $this->assertInstanceOf(DumpFieldsMapCommand::class, $dumpFieldsMapCommand);

        $diagnosticCommand = $container->get('doctrine_mongodb.odm.command.encryption_diagnostic');
        $this->assertInstanceOf(DiagnosticCommand::class, $diagnosticCommand);
    }

    public function testAutoEncryptionWithKeyVaultClientService(): void
    {
        self::requireAutoEncryptionSupportInODM();

        $container = $this->buildMinimalContainer();
        $loader    = new DoctrineMongoDBExtension();

        // Define a dummy service for the keyVaultClient
        $dummyServiceId = 'my_key_vault_client_service';
        $container->setDefinition($dummyServiceId, new Definition(Client::class));

        $config = [
            'connections' => [
                'default' => [
                    'autoEncryption' => [
                        'keyVaultNamespace' => 'db.vault',
                        'keyVaultClient' => $dummyServiceId,
                        'kmsProvider' => ['type' => 'local', 'key' => 'base64_encoded_key'],
                    ],
                ],
            ],
            'document_managers' => ['default' => []],
        ];

        $loader->load([$config], $container);
        (new ServiceRepositoryCompilerPass())->process($container);

        $clientDef     = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $driverOptions = $clientDef->getArgument(2);

        self::assertArrayHasKey('autoEncryption', $driverOptions);
        self::assertInstanceOf(Reference::class, $driverOptions['autoEncryption']['keyVaultClient']);
        self::assertEquals($dummyServiceId, (string) $driverOptions['autoEncryption']['keyVaultClient']);
        self::assertEquals('db.vault', $driverOptions['autoEncryption']['keyVaultNamespace']);
        self::assertEquals(['local' => ['key' => 'base64_encoded_key']], $driverOptions['autoEncryption']['kmsProviders']);

        // Auto encryption configuration should be set in the ODM configuration
        $odmConfiguration = $container->get('doctrine_mongodb.odm.default_configuration');
        self::assertInstanceOf(Configuration::class, $odmConfiguration);
        self::assertSame('local', $odmConfiguration->getDefaultKmsProvider());
        self::assertNull($odmConfiguration->getDefaultMasterKey());
        self::assertArrayHasKey('autoEncryption', $odmConfiguration->getDriverOptions());
        self::assertInstanceOf(Client::class, $odmConfiguration->getDriverOptions()['autoEncryption']['keyVaultClient']);

        // Ensure the driver option set in the client matches the ODM configuration
        // except for the keyVaultClient, which is a service reference
        self::assertEquals(
            array_diff_key($driverOptions['autoEncryption'], ['keyVaultClient' => false]),
            array_diff_key($odmConfiguration->getDriverOptions()['autoEncryption'], ['keyVaultClient' => false]),
        );
    }

    public function testAutoEncryptionWithComplexKmsAndSchemaMap(): void
    {
        self::requireAutoEncryptionSupportInODM();

        $container = $this->buildMinimalContainer();
        $loader    = new DoctrineMongoDBExtension();

        $schemaMap = [
            'db.coll.users' => [
                'bsonType' => 'object',
                'encryptMetadata' => ['keyId' => '/dataKeyId'],
                'properties' => ['ssn' => ['encrypt' => ['bsonType' => 'string', 'algorithm' => 'AEAD_AES_256_CBC_HMAC_SHA_512-Deterministic']]],
            ],
        ];
        $masterKey = [
            'region' => 'eu-west-3',
            'key' => 'arn:aws:kms:eu-west-3:123456789012:key/abcd1234-a123-456a-a12b-a123b4cd56ef',
        ];
        $config    = [
            'connections' => [
                'default' => [
                    'autoEncryption' => [
                        'keyVaultNamespace' => 'db.vault',
                        'kmsProvider' => ['type' => 'aws', 'accessKeyId' => 'test', 'secretAccessKey' => 'secret'],
                        'schemaMap' => $schemaMap,
                        'masterKey' => $masterKey,
                    ],
                ],
            ],
            'document_managers' => ['default' => []],
        ];

        $loader->load([$config], $container);
        (new ServiceRepositoryCompilerPass())->process($container);

        $clientDef     = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $driverOptions = $clientDef->getArgument(2);

        self::assertArrayHasKey('autoEncryption', $driverOptions);
        self::assertEquals(['aws' => ['accessKeyId' => 'test', 'secretAccessKey' => 'secret']], $driverOptions['autoEncryption']['kmsProviders']);
        self::assertEquals($schemaMap, $driverOptions['autoEncryption']['schemaMap']);
        self::assertEquals('db.vault', $driverOptions['autoEncryption']['keyVaultNamespace']);

        // Auto encryption configuration should be set in the ODM configuration
        $odmConfiguration = $container->get('doctrine_mongodb.odm.default_configuration');
        self::assertInstanceOf(Configuration::class, $odmConfiguration);
        self::assertSame('aws', $odmConfiguration->getDefaultKmsProvider());
        self::assertSame($masterKey, $odmConfiguration->getDefaultMasterKey());
        self::assertArrayHasKey('autoEncryption', $odmConfiguration->getDriverOptions());

        // Ensure the driver option set in the client matches the ODM configuration
        self::assertEquals($driverOptions['autoEncryption'], $odmConfiguration->getDriverOptions()['autoEncryption']);
    }

    public function testAutoEncryptionWithExtraOptions(): void
    {
        self::requireAutoEncryptionSupportInODM();

        $container = $this->buildMinimalContainer();
        $loader    = new DoctrineMongoDBExtension();

        $config = [
            'connections' => [
                'default' => [
                    'autoEncryption' => [
                        'keyVaultNamespace' => 'db.vault',
                        'kmsProvider' => ['type' => 'local', 'key' => 'base64_encoded_key'],
                        'extraOptions' => [
                            'cryptSharedLibPath' => '/another/path.so',
                            'cryptSharedLibRequired' => false,
                            'mongocryptdSpawnPath' => '/custom/mongocryptd',
                        ],
                    ],
                ],
            ],
            'document_managers' => ['default' => []],
        ];

        $loader->load([$config], $container);
        (new ServiceRepositoryCompilerPass())->process($container);

        $clientDef     = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $driverOptions = $clientDef->getArgument(2);

        self::assertArrayHasKey('autoEncryption', $driverOptions);
        self::assertEquals('/another/path.so', $driverOptions['autoEncryption']['extraOptions']['cryptSharedLibPath']);
        self::assertFalse($driverOptions['autoEncryption']['extraOptions']['cryptSharedLibRequired']);
        self::assertEquals('/custom/mongocryptd', $driverOptions['autoEncryption']['extraOptions']['mongocryptdSpawnPath']);

        self::assertArrayHasKey('typeMap', $driverOptions); // Default option
        self::assertArrayHasKey('driver', $driverOptions); // Added by normalizeDriverOptions
        self::assertEquals('symfony-mongodb', $driverOptions['driver']['name']);
        self::assertArrayHasKey('version', $driverOptions['driver']);

        // Auto encryption configuration should be set in the ODM configuration
        $odmConfiguration = $container->get('doctrine_mongodb.odm.default_configuration');
        self::assertInstanceOf(Configuration::class, $odmConfiguration);
        self::assertSame('local', $odmConfiguration->getDefaultKmsProvider());
        self::assertNull($odmConfiguration->getDefaultMasterKey());
        self::assertArrayHasKey('autoEncryption', $odmConfiguration->getDriverOptions());

        // Ensure the driver option set in the client matches the ODM configuration
        self::assertEquals($driverOptions['autoEncryption'], $odmConfiguration->getDriverOptions()['autoEncryption']);
    }

    public function testAutoEncryptionWithEmptyKmsProvider(): void
    {
        self::requireAutoEncryptionSupportInODM();

        $container = $this->buildMinimalContainer();
        $loader    = new DoctrineMongoDBExtension();

        $config = [
            'connections' => [
                'default' => [
                    'autoEncryption' => [
                        'keyVaultNamespace' => 'db.vault',
                        'kmsProvider' => ['type' => 'aws'],
                    ],
                ],
            ],
            'document_managers' => ['default' => []],
        ];

        $loader->load([$config], $container);
        (new ServiceRepositoryCompilerPass())->process($container);

        $clientDef     = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $driverOptions = $clientDef->getArgument(2);

        self::assertArrayHasKey('autoEncryption', $driverOptions);
        self::assertEquals(['aws' => new Definition(stdClass::class)], $driverOptions['autoEncryption']['kmsProviders']);
    }

    public function testAutoEncryptionMinimumODMVersion(): void
    {
        if (InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.12@dev')) {
            self::markTestSkipped('Installed version of doctrine/mongodb-odm does support auto encryption');
        }

        $container = $this->buildMinimalContainer();
        $loader    = new DoctrineMongoDBExtension();

        $config = [
            'connections' => [
                'default' => [
                    'autoEncryption' => [
                        'kmsProvider' => ['type' => 'aws'],
                    ],
                ],
            ],
            'document_managers' => ['default' => []],
        ];

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('The "autoEncryption" option requires doctrine/mongodb-odm version 2.12 or higher');
        $loader->load([$config], $container);
    }

    /** @phpstan-param ConfigurationArray $config */
    #[DataProvider('provideLazyObjectConfigurations')]
    public function testRegistryGetManagerForClass(array $config): void
    {
        $container = $this->getContainer('AttributesBundle');
        $loader    = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration($config + [
            'connections' => [
                'default' => [],
            ],
            'document_managers' => [
                'default' => [
                    'mappings' => [
                        'AttributesBundle' => ['type' => 'attribute'],
                    ],
                ],
            ],
        ]), $container);
        (new ResolveParameterPlaceHoldersPass())->process($container);
        (new ServiceRepositoryCompilerPass())->process($container);
        (new CreateProxyDirectoryPass())->process($container);

        $container->compile();
        $registry = $container->get('doctrine_mongodb');
        $dm       = $container->get('doctrine_mongodb.odm.document_manager');

        self::assertInstanceOf(ManagerRegistry::class, $registry);
        self::assertInstanceOf(DocumentManager::class, $dm);

        // Create a lazy object
        $ref = $dm->getReference(TestDocument::class, 'some');
        self::assertInstanceOf(TestDocument::class, $ref);
        self::assertSame($dm, $registry->getManagerForClass($ref::class), 'The manager is found for the proxy document class');
    }

    /** @phpstan-return iterable<ConfigurationArray> */
    public static function provideLazyObjectConfigurations(): iterable
    {
        if (interface_exists(GhostObjectInterface::class)) {
            yield 'Proxy Manager' => [
                ['enable_lazy_ghost_objects' => false, 'enable_native_lazy_objects' => false],
            ];
        }

        if (trait_exists(LazyGhostTrait::class) && method_exists(Configuration::class, 'setUseLazyGhostObject')) {
            yield 'Symfony Lazy Objects' => [
                ['enable_lazy_ghost_objects' => true, 'enable_native_lazy_objects' => false],
            ];
        }

        if (PHP_VERSION_ID >= 80400 && method_exists(Configuration::class, 'setUseNativeLazyObject')) {
            yield 'Native Lazy Objects' => [
                ['enable_lazy_ghost_objects' => false, 'enable_native_lazy_objects' => true],
            ];
        }
    }

    private static function requireAutoEncryptionSupportInODM(): void
    {
        if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.12@dev')) {
            self::markTestSkipped('Installed version of doctrine/mongodb-odm does not support auto encryption');
        }
    }
}
