<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use function array_merge;
use function sys_get_temp_dir;

class DoctrineMongoDBExtensionTest extends TestCase
{
    public static function buildConfiguration(array $settings = [])
    {
        return [array_merge(
            [
                'connections' => ['default' => []],
                'document_managers' => ['default' => []],
            ],
            $settings
        ),
        ];
    }

    public function buildMinimalContainer()
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
    public function testParameterOverride($option, $parameter, $value)
    {
        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration([$option => $value]), $container);

        $this->assertEquals($value, $container->getParameter('doctrine_mongodb.odm.' . $parameter));
    }

    private function getContainer($bundles = 'OtherXmlBundle')
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

    public function parameterProvider()
    {
        return [
            ['proxy_namespace', 'proxy_namespace', 'foo'],
            ['proxy-namespace', 'proxy_namespace', 'bar'],
        ];
    }

    public function getAutomappingConfigurations()
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
    public function testAutomapping(array $documentManagers)
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

    public function testFactoriesAreRegistered()
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
        $this->assertContains(
            [
                'setRepositoryFactory',
                [new Reference('repository_factory_service')],
            ],
            $configDm1->getMethodCalls()
        );
        $this->assertContains(
            [
                'setPersistentCollectionFactory',
                [new Reference('persistent_collection_factory_service')],
            ],
            $configDm1->getMethodCalls()
        );
    }

    public function testPublicServicesAndAliases()
    {
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $this->assertTrue($container->getDefinition('doctrine_mongodb')->isPublic());
        $this->assertTrue($container->getDefinition('doctrine_mongodb.odm.default_document_manager')->isPublic());
        $this->assertTrue($container->getAlias('doctrine_mongodb.odm.document_manager')->isPublic());
    }
}
