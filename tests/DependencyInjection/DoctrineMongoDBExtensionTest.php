<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Closure;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\DocumentListenerBundle\EventListener\TestAttributeListener;
use Doctrine\ODM\MongoDB\Mapping\Annotations;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Messenger\DoctrineClearEntityManagerWorkerSubscriber;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\MessageBusInterface;

use function array_merge;
use function class_exists;
use function interface_exists;
use function is_dir;
use function method_exists;
use function sprintf;
use function sys_get_temp_dir;

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
            'kernel.name'             => 'kernel',
            'kernel.environment'      => 'test',
            'kernel.debug'            => 'true',
            'kernel.bundles'          => [],
            'kernel.bundles_metadata' => [],
            'kernel.container_class'  => Container::class,
        ]));
    }

    /** @dataProvider parameterProvider */
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

    /** @dataProvider provideAttributeExcludedFromContainer */
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

    /** @dataProvider getAutomappingConfigurations */
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
        if (! interface_exists(MessageBusInterface::class)) {
            $this->markTestSkipped('Symfony Messenger component is not installed');
        }

        if (! class_exists(DoctrineClearEntityManagerWorkerSubscriber::class)) {
            $this->markTestSkipped('DoctrineClearEntityManagerWorkerSubscriber is not available in symfony/doctrine-bridge');
        }

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
}
