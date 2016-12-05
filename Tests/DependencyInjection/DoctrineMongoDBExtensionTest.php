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

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMongoDBExtensionTest extends \PHPUnit_Framework_TestCase
{
    public static function buildConfiguration(array $settings = [])
    {
        return [array_merge(
            [
                'connections' => ['dummy' => []],
                'document_managers' => ['dummy' => []],
            ],
            $settings
        )];
    }

    public function buildMinimalContainer()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.root_dir'    => __DIR__,
            'kernel.name'        => 'kernel',
            'kernel.environment' => 'test',
            'kernel.debug'       => 'true',
            'kernel.bundles'     => [],
        ]));
        return $container;
    }

    public function testBackwardCompatibilityAliases()
    {
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $this->assertEquals('doctrine_mongodb.odm.document_manager', (string) $container->getAlias('doctrine.odm.mongodb.document_manager'));
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

        $this->assertEquals($value, $container->getParameter('doctrine_mongodb.odm.'.$parameter));
    }

    private function getContainer($bundles = 'YamlBundle')
    {
        $bundles = (array) $bundles;

        $map = [];
        foreach ($bundles as $bundle) {
            require_once __DIR__.'/Fixtures/Bundles/'.$bundle.'/'.$bundle.'.php';

            $map[$bundle] = 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\'.$bundle.'\\'.$bundle;
        }

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'       => false,
            'kernel.bundles'     => $map,
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.name'        => 'kernel',
            'kernel.root_dir'    => __DIR__.'/../../' // src dir
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
                        'mappings' => [
                            'YamlBundle' => null
                        ]
                    ],
                    'dm2' => [
                        'mappings' => [
                            'XmlBundle' => null
                        ]
                    ]
                ]
            ],
            [
                [
                    'dm1' => [
                        'auto_mapping' => true
                    ],
                    'dm2' => [
                        'mappings' => [
                            'XmlBundle' => null
                        ]
                    ]
                ]
            ],
            [
                [
                    'dm1' => [
                        'auto_mapping' => true,
                        'mappings' => [
                            'YamlBundle' => null
                        ]
                    ],
                    'dm2' => [
                        'mappings' => [
                            'XmlBundle' => null
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getAutomappingConfigurations
     */
    public function testAutomapping(array $documentManagers)
    {
        $container = $this->getContainer([
            'YamlBundle',
            'XmlBundle'
        ]);

        $loader = new DoctrineMongoDBExtension();

        $loader->load(
            [
                [
                    'default_database' => 'test_database',
                    'connections' => [
                        'cn1' => [],
                        'cn2' => []
                    ],
                    'document_managers' => $documentManagers
                ]
            ], $container);

        $configDm1 = $container->getDefinition('doctrine_mongodb.odm.dm1_configuration');
        $configDm2 = $container->getDefinition('doctrine_mongodb.odm.dm2_configuration');

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    [
                        'YamlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\YamlBundle\Document'
                    ]
                ]
            ], $configDm1->getMethodCalls());

        $this->assertContains(
            [
                'setDocumentNamespaces',
                [
                    [
                        'XmlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document'
                    ]
                ]
            ], $configDm2->getMethodCalls());
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
                        'cn2' => []
                    ],
                    'document_managers' => [
                        'dm1' => [
                            'repository_factory' => 'repository_factory_service',
                            'persistent_collection_factory' => 'persistent_collection_factory_service',
                        ]
                    ]
                ]
            ], $container);

        $configDm1 = $container->getDefinition('doctrine_mongodb.odm.dm1_configuration');
        $this->assertContains(
            [
                'setRepositoryFactory',
                [new Reference('repository_factory_service')]
            ], $configDm1->getMethodCalls());
        $this->assertContains(
            [
                'setPersistentCollectionFactory',
                [new Reference('persistent_collection_factory_service')]
            ], $configDm1->getMethodCalls());
    }
}
