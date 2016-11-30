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

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\AddValidatorNamespaceAliasPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;
use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\YamlDriver;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use PHPUnit_Framework_AssertionFailedError;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractMongoDBExtensionTest extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    public function testDependencyInjectionConfigurationDefaults()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();

        $loader->load(DoctrineMongoDBExtensionTest::buildConfiguration(), $container);

        $this->assertEquals(Connection::class, $container->getParameter('doctrine_mongodb.odm.connection.class'));
        $this->assertEquals(Configuration::class, $container->getParameter('doctrine_mongodb.odm.configuration.class'));
        $this->assertEquals(DocumentManager::class, $container->getParameter('doctrine_mongodb.odm.document_manager.class'));
        $this->assertEquals('MongoDBODMProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(false, $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
        $this->assertEquals(ArrayCache::class, $container->getParameter('doctrine_mongodb.odm.cache.array.class'));
        $this->assertEquals(ApcCache::class, $container->getParameter('doctrine_mongodb.odm.cache.apc.class'));
        $this->assertEquals(MemcacheCache::class, $container->getParameter('doctrine_mongodb.odm.cache.memcache.class'));
        $this->assertEquals('localhost', $container->getParameter('doctrine_mongodb.odm.cache.memcache_host'));
        $this->assertEquals('11211', $container->getParameter('doctrine_mongodb.odm.cache.memcache_port'));
        $this->assertEquals('Memcache', $container->getParameter('doctrine_mongodb.odm.cache.memcache_instance.class'));
        $this->assertEquals(XcacheCache::class, $container->getParameter('doctrine_mongodb.odm.cache.xcache.class'));
        $this->assertEquals(MappingDriverChain::class, $container->getParameter('doctrine_mongodb.odm.metadata.driver_chain.class'));
        $this->assertEquals(AnnotationDriver::class, $container->getParameter('doctrine_mongodb.odm.metadata.annotation.class'));
        $this->assertEquals(XmlDriver::class, $container->getParameter('doctrine_mongodb.odm.metadata.xml.class'));
        $this->assertEquals(YamlDriver::class, $container->getParameter('doctrine_mongodb.odm.metadata.yml.class'));

        $this->assertEquals(UniqueEntityValidator::class, $container->getParameter('doctrine_odm.mongodb.validator.unique.class'));

        $config = DoctrineMongoDBExtensionTest::buildConfiguration([
            'proxy_namespace' => 'MyProxies',
            'auto_generate_proxy_classes' => true,
            'connections' => ['default' => []],
            'document_managers' => ['default' => []]
        ]);
        $loader->load($config, $container);

        $this->assertEquals('MyProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(true, $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals(null, $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        $this->assertEquals(['%doctrine_mongodb.odm.document_manager.class%', 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[1]);
    }

    public function testSingleDocumentManagerConfiguration()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();

        $config = [
            'connections' => [
                'default' => [
                    'server' => 'mongodb://localhost:27017',
                    'options' => ['connect' => true]
                ]
            ],
            'document_managers' => ['default' => []]
        ];
        $loader->load([$config], $container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(['connect' => true], $arguments[1]);
        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        $this->assertEquals(['%doctrine_mongodb.odm.document_manager.class%', 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[1]);
    }

    public function testLoadSimpleSingleConnection()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_simple_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(['connect' => true], $arguments[1]);
        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $methodCalls = $definition->getMethodCalls();
        $methodNames = array_map(function($call) { return $call[0]; }, $methodCalls);
        $this->assertInternalType('integer', $pos = array_search('setDefaultDB', $methodNames));
        $this->assertEquals('mydb', $methodCalls[$pos][1][0]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        $this->assertEquals(['%doctrine_mongodb.odm.document_manager.class%', 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[1]);

        $this->assertEquals('doctrine_mongodb.odm.default_document_manager', (string) $container->getAlias('doctrine_mongodb.odm.document_manager'));
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $container->getAlias('doctrine_mongodb.odm.event_manager'));
    }

    public function testLoadSingleConnection()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(['connect' => true], $arguments[1]);
        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        $this->assertEquals(['%doctrine_mongodb.odm.document_manager.class%', 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[1]);

        $this->assertEquals('doctrine_mongodb.odm.default_document_manager', (string) $container->getAlias('doctrine_mongodb.odm.document_manager'));
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $container->getAlias('doctrine_mongodb.odm.event_manager'));
    }

    public function testLoadMultipleConnections()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_multiple_connections');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn1_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(['connect' => true], $arguments[1]);
        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_configuration', (string) $arguments[2]);
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm1_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        $this->assertEquals(['%doctrine_mongodb.odm.document_manager.class%', 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.dm1_configuration', (string) $arguments[1]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn2_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(['connect' => true], $arguments[1]);
        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_configuration', (string) $arguments[2]);
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm2_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        $this->assertEquals(['%doctrine_mongodb.odm.document_manager.class%', 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.dm2_configuration', (string) $arguments[1]);

        $this->assertEquals('doctrine_mongodb.odm.dm2_document_manager', (string) $container->getAlias('doctrine_mongodb.odm.document_manager'));
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection.event_manager', (string) $container->getAlias('doctrine_mongodb.odm.event_manager'));
    }

    public function testBundleDocumentAliases()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();

        $config = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['YamlBundle' => []]]]]
        );
        $loader->load($config, $container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $calls = $definition->getMethodCalls();
        $this->assertTrue(isset($calls[0][1][0]['YamlBundle']));
        $this->assertEquals('DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\YamlBundle\Document', $calls[0][1][0]['YamlBundle']);
    }

    public function testYamlBundleMappingDetection()
    {
        $container = $this->getContainer('YamlBundle');
        $loader = new DoctrineMongoDBExtension();
        $config = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['YamlBundle' => []]]]]
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_yml_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\YamlBundle\Document', $calls[0][1][1]);
    }

    public function testXmlBundleMappingDetection()
    {
        $container = $this->getContainer('XmlBundle');
        $loader = new DoctrineMongoDBExtension();
        $config = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['XmlBundle' => []]]]]
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_xml_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document', $calls[0][1][1]);
    }

    public function testAnnotationsBundleMappingDetection()
    {
        $container = $this->getContainer('AnnotationsBundle');
        $loader = new DoctrineMongoDBExtension();
        $config = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['AnnotationsBundle' => []]]]]
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_annotation_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\AnnotationsBundle\Document', $calls[0][1][1]);
    }

    public function testDocumentManagerMetadataCacheDriverConfiguration()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_multiple_connections');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm1_metadata_cache');
        $this->assertEquals('%doctrine_mongodb.odm.cache.xcache.class%', $definition->getClass());

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm2_metadata_cache');
        $this->assertEquals('%doctrine_mongodb.odm.cache.apc.class%', $definition->getClass());
    }

    public function testDocumentManagerMemcacheMetadataCacheDriverConfiguration()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_simple_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_metadata_cache');
        $this->assertEquals(MemcacheCache::class, $definition->getClass());

        $calls = $definition->getMethodCalls();
        $this->assertEquals('setMemcache', $calls[0][0]);
        $this->assertEquals('doctrine_mongodb.odm.default_memcache_instance', (string) $calls[0][1][0]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_memcache_instance');
        $this->assertEquals('Memcache', $definition->getClass());

        $calls = $definition->getMethodCalls();
        $this->assertEquals('connect', $calls[0][0]);
        $this->assertEquals('localhost', $calls[0][1][0]);
        $this->assertEquals(11211, $calls[0][1][1]);
    }

    public function testDependencyInjectionImportsOverrideDefaults()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);
        $config = DoctrineMongoDBExtensionTest::buildConfiguration();
        $container->prependExtensionConfig($loader->getAlias(), reset($config));

        $this->loadFromFile($container, 'odm_imports');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $this->assertTrue((bool) $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
    }

    public function testResolveTargetDocument()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_resolve_target_document');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.listeners.resolve_target_document');
        $this->assertDefinitionMethodCallOnce($definition, 'addResolveTargetDocument', [UserInterface::class, 'MyUserClass', []]);
        $this->assertEquals(['doctrine_mongodb.odm.event_listener' => [['event' => 'loadClassMetadata']]], $definition->getTags());
    }

    public function testFilters()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_filters');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $complexParameters = [
            'integer' => 1,
            'string' => 'foo',
            'object' => ['key' => 'value'],
            'array' => [1, 2, 3],
        ];

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', ['disabled_filter', \Vendor\Filter\DisabledFilter::class, []]);
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', ['basic_filter', \Vendor\Filter\BasicFilter::class, []]);
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', ['complex_filter', \Vendor\Filter\ComplexFilter::class, $complexParameters]);

        $enabledFilters = ['basic_filter', 'complex_filter'];

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_manager_configurator');
        $this->assertEquals($enabledFilters, $definition->getArgument(0), 'Only enabled filters are passed to the ManagerConfigurator.');
    }

    /**
     * Asserts that the given definition contains a call to the method that uses
     * the specified parameters.
     *
     * @param Definition $definition
     * @param string     $methodName
     * @param array      $params
     */
    private function assertDefinitionMethodCallAny(Definition $definition, $methodName, array $params)
    {
        $calls = $definition->getMethodCalls();
        $called = false;
        $lastError = null;

        foreach ($calls as $call) {
            if ($call[0] !== $methodName) {
                continue;
            }

            $called = true;

            try {
                $this->assertSame($params, $call[1], "Expected parameters to method '" . $methodName . "' did not match the actual parameters.");
                return;
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $lastError = $e;
            }
        }

        if ( ! $called) {
            $this->fail("Method '" . $methodName . "' is expected to be called, but it was never called.");
        }

        if ($lastError) {
            throw $lastError;
        }
    }

    /**
     * Asserts that the given definition contains exactly one call to the method
     * and that it uses the specified parameters.
     *
     * @param Definition $definition
     * @param string     $methodName
     * @param array      $params
     */
    private function assertDefinitionMethodCallOnce(Definition $definition, $methodName, array $params)
    {
        $calls = $definition->getMethodCalls();
        $called = false;

        foreach ($calls as $call) {
            if ($call[0] !== $methodName) {
                continue;
            }

            if ($called) {
                $this->fail("Method '" . $methodName . "' is expected to be called only once, but it was called multiple times.");
            }

            $called = true;

            $this->assertEquals($params, $call[1], "Expected parameters to method '" . $methodName . "' did not match the actual parameters.");
        }

        if ( ! $called) {
            $this->fail("Method '" . $methodName . "' is expected to be called once, but it was never called.");
        }
    }

    protected function getContainer($bundle = 'YamlBundle')
    {
        require_once __DIR__.'/Fixtures/Bundles/'.$bundle.'/'.$bundle.'.php';

        return new ContainerBuilder(new ParameterBag([
            'kernel.bundles'          => [$bundle => 'DoctrineMongoDBBundle\\Tests\\DependencyInjection\\Fixtures\\Bundles\\'.$bundle.'\\'.$bundle],
            'kernel.cache_dir'        => __DIR__,
            'kernel.compiled_classes' => [],
            'kernel.debug'            => false,
            'kernel.environment'      => 'test',
            'kernel.name'             => 'kernel',
            'kernel.root_dir'         => __DIR__,
        ]));
    }
}
