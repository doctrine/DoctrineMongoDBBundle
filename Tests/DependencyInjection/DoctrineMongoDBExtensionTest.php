<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Messenger\DoctrineClearEntityManagerWorkerSubscriber;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\MessageBusInterface;

use function array_merge;
use function class_exists;
use function interface_exists;
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
                $settings
            ),
        ];
    }

    public function buildMinimalContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.root_dir'        => __DIR__,
            'kernel.project_dir'     => __DIR__,
            'kernel.name'            => 'kernel',
            'kernel.environment'     => 'test',
            'kernel.debug'           => 'true',
            'kernel.bundles'         => [],
            'kernel.container_class' => Container::class,
        ]));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testParameterOverride(string $option, string $parameter, string $value): void
    {
        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration([$option => $value]), $container);

        $this->assertEquals($value, $container->getParameter('doctrine_mongodb.odm.' . $parameter));
    }

    /**
     * @param string|string[] $bundles
     */
    private function getContainer($bundles = 'OtherXmlBundle'): ContainerBuilder
    {
        $bundles = (array) $bundles;

        $map = [];
        foreach ($bundles as $bundle) {
            require_once __DIR__ . '/Fixtures/Bundles/' . $bundle . '/' . $bundle . '.php';

            $map[$bundle] = 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\' . $bundle . '\\' . $bundle;
        }

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'           => false,
            'kernel.bundles'         => $map,
            'kernel.cache_dir'       => sys_get_temp_dir(),
            'kernel.environment'     => 'test',
            'kernel.name'            => 'kernel',
            'kernel.root_dir'        => __DIR__ . '/../../',
            'kernel.project_dir'     => __DIR__ . '/../../',
            'kernel.container_class' => Container::class,
        ]));
    }

    public function parameterProvider(): array
    {
        return [
            ['proxy_namespace', 'proxy_namespace', 'foo'],
            ['proxy-namespace', 'proxy_namespace', 'bar'],
        ];
    }

    public function getAutomappingConfigurations(): array
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
                ],
            ],
        ];
    }

    /**
     * @dataProvider getAutomappingConfigurations
     */
    public function testAutomapping(array $documentManagers): void
    {
        $container = $this->getContainer([
            'OtherXmlBundle',
            'XmlBundle',
        ]);

        $loader = new DoctrineMongoDBExtension();

        $loader->load(
            [
                [
                    'default_database' => 'test_database',
                    'connections' => [
                        'cn1' => [],
                        'cn2' => [],
                    ],
                    'document_managers' => $documentManagers,
                ],
            ],
            $container
        );

        $configDm1 = $container->getDefinition('doctrine_mongodb.odm.dm1_configuration');
        $configDm2 = $container->getDefinition('doctrine_mongodb.odm.dm2_configuration');

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    ['OtherXmlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\OtherXmlBundle\Document'],
                ],
            ],
            $configDm1->getMethodCalls()
        );

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    ['XmlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document'],
                ],
            ],
            $configDm2->getMethodCalls()
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
            $container
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
        /** @psalm-suppress UndefinedClass Optional dependency */
        if (! interface_exists(MessageBusInterface::class)) {
            $this->markTestSkipped('Symfony Messenger component is not installed');
        }

        if (! class_exists(DoctrineClearEntityManagerWorkerSubscriber::class)) {
            $this->markTestSkipped('DoctrineClearEntityManagerWorkerSubscriber is not available in symfony/doctrine-bridge');
        }

        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $this->assertNotNull($subscriber = $container->getDefinition('doctrine_mongodb.messenger.event_subscriber.doctrine_clear_document_manager'));
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
}
