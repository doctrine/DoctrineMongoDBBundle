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
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use PHPUnit_Framework_AssertionFailedError;

abstract class AbstractMongoDBExtensionTest extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    public function testDependencyInjectionConfigurationDefaults()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();

        $loader->load(DoctrineMongoDBExtensionTest::buildConfiguration(), $container);

        $this->assertEquals('Doctrine\MongoDB\Connection', $container->getParameter('doctrine_mongodb.odm.connection.class'));
        $this->assertEquals('Doctrine\ODM\MongoDB\Configuration', $container->getParameter('doctrine_mongodb.odm.configuration.class'));
        $this->assertEquals('Doctrine\ODM\MongoDB\DocumentManager', $container->getParameter('doctrine_mongodb.odm.document_manager.class'));
        $this->assertEquals('MongoDBODMProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(false, $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
        $this->assertEquals('Doctrine\Common\Cache\ArrayCache', $container->getParameter('doctrine_mongodb.odm.cache.array.class'));
        $this->assertEquals('Doctrine\Common\Cache\ApcCache', $container->getParameter('doctrine_mongodb.odm.cache.apc.class'));
        $this->assertEquals('Doctrine\Common\Cache\MemcacheCache', $container->getParameter('doctrine_mongodb.odm.cache.memcache.class'));
        $this->assertEquals('localhost', $container->getParameter('doctrine_mongodb.odm.cache.memcache_host'));
        $this->assertEquals('11211', $container->getParameter('doctrine_mongodb.odm.cache.memcache_port'));
        $this->assertEquals('Memcache', $container->getParameter('doctrine_mongodb.odm.cache.memcache_instance.class'));
        $this->assertEquals('Doctrine\Common\Cache\XcacheCache', $container->getParameter('doctrine_mongodb.odm.cache.xcache.class'));
        $this->assertEquals('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain', $container->getParameter('doctrine_mongodb.odm.metadata.driver_chain.class'));
        $this->assertEquals('Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver', $container->getParameter('doctrine_mongodb.odm.metadata.annotation.class'));
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver', $container->getParameter('doctrine_mongodb.odm.metadata.xml.class'));
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Mapping\Driver\YamlDriver', $container->getParameter('doctrine_mongodb.odm.metadata.yml.class'));

        $this->assertEquals('Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator', $container->getParameter('doctrine_odm.mongodb.validator.unique.class'));

        $config = DoctrineMongoDBExtensionTest::buildConfiguration(array(
            'proxy_namespace' => 'MyProxies',
            'auto_generate_proxy_classes' => true,
            'connections' => array('default' => array()),
            'document_managers' => array('default' => array())
        ));
        $loader->load($config, $container);

        $this->assertEquals('MyProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(true, $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals(null, $arguments[0]);
        $this->assertEquals(array(), $arguments[1]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array('%doctrine_mongodb.odm.document_manager.class%', 'create'), $definition->getFactory());
        } else {
            $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getFactoryClass());
            $this->assertEquals('create', $definition->getFactoryMethod());
        }
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[1]);
    }

    public function testSingleDocumentManagerConfiguration()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();

        $config = array(
            'connections' => array(
                'default' => array(
                    'server' => 'mongodb://localhost:27017',
                    'options' => array('connect' => true)
                )
            ),
            'document_managers' => array('default' => array())
        );
        $loader->load(array($config), $container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(array('connect' => true), $arguments[1]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array('%doctrine_mongodb.odm.document_manager.class%', 'create'), $definition->getFactory());
        } else {
            $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getFactoryClass());
            $this->assertEquals('create', $definition->getFactoryMethod());
        }
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[1]);
    }

    public function testLoadSimpleSingleConnection()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_simple_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(array('connect' => true), $arguments[1]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $methodCalls = $definition->getMethodCalls();
        $methodNames = array_map(function($call) { return $call[0]; }, $methodCalls);
        $this->assertInternalType('integer', $pos = array_search('setDefaultDB', $methodNames));
        $this->assertEquals('mydb', $methodCalls[$pos][1][0]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array('%doctrine_mongodb.odm.document_manager.class%', 'create'), $definition->getFactory());
        } else {
            $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getFactoryClass());
            $this->assertEquals('create', $definition->getFactoryMethod());
        }
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[1]);
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

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(array('connect' => true), $arguments[1]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.default_configuration', (string) $arguments[2]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array('%doctrine_mongodb.odm.document_manager.class%', 'create'), $definition->getFactory());
        } else {
            $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getFactoryClass());
            $this->assertEquals('create', $definition->getFactoryMethod());
        }
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.default_connection', (string) $arguments[0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[1]);
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

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn1_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(array('connect' => true), $arguments[1]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_configuration', (string) $arguments[2]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm1_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array('%doctrine_mongodb.odm.document_manager.class%', 'create'), $definition->getFactory());
        } else {
            $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getFactoryClass());
            $this->assertEquals('create', $definition->getFactoryMethod());
        }
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_connection', (string) $arguments[0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.dm1_configuration', (string) $arguments[1]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn2_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals(array('connect' => true), $arguments[1]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[2]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_configuration', (string) $arguments[2]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[3]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection.event_manager', (string) $arguments[3]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm2_document_manager');
        $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getClass());
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array('%doctrine_mongodb.odm.document_manager.class%', 'create'), $definition->getFactory());
        } else {
            $this->assertEquals('%doctrine_mongodb.odm.document_manager.class%', $definition->getFactoryClass());
            $this->assertEquals('create', $definition->getFactoryMethod());
        }
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection', (string) $arguments[0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.dm2_configuration', (string) $arguments[1]);

        $this->assertEquals('doctrine_mongodb.odm.dm2_document_manager', (string) $container->getAlias('doctrine_mongodb.odm.document_manager'));
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection.event_manager', (string) $container->getAlias('doctrine_mongodb.odm.event_manager'));
    }

    public function testBundleDocumentAliases()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();

        $config = DoctrineMongoDBExtensionTest::buildConfiguration(
            array('document_managers' => array('default' => array('mappings' => array('YamlBundle' => array()))))
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
            array('document_managers' => array('default' => array('mappings' => array('YamlBundle' => array()))))
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
            array('document_managers' => array('default' => array('mappings' => array('XmlBundle' => array()))))
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
            array('document_managers' => array('default' => array('mappings' => array('AnnotationsBundle' => array()))))
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

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
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

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_metadata_cache');
        $this->assertEquals('Doctrine\Common\Cache\MemcacheCache', $definition->getClass());

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

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $this->assertTrue($container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
    }

    public function testResolveTargetDocument()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_resolve_target_document');

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.listeners.resolve_target_document');
        $this->assertDefinitionMethodCallOnce($definition, 'addResolveTargetDocument', array('Symfony\Component\Security\Core\User\UserInterface', 'MyUserClass', array()));
        $this->assertEquals(array('doctrine_mongodb.odm.event_listener' => array(array('event' => 'loadClassMetadata'))), $definition->getTags());
    }

    public function testFilters()
    {
        $container = $this->getContainer();
        $loader = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_filters');

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        $complexParameters = array(
            'integer' => 1,
            'string' => 'foo',
            'object' => array('key' => 'value'),
            'array' => array(1, 2, 3),
        );

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', array('disabled_filter', 'Vendor\Filter\DisabledFilter', array()));
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', array('basic_filter', 'Vendor\Filter\BasicFilter', array()));
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', array('complex_filter', 'Vendor\Filter\ComplexFilter', $complexParameters));

        $enabledFilters = array('basic_filter', 'complex_filter');

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

        return new ContainerBuilder(new ParameterBag(array(
            'kernel.bundles'          => array($bundle => 'DoctrineMongoDBBundle\\Tests\\DependencyInjection\\Fixtures\\Bundles\\'.$bundle.'\\'.$bundle),
            'kernel.cache_dir'        => __DIR__,
            'kernel.compiled_classes' => array(),
            'kernel.debug'            => false,
            'kernel.environment'      => 'test',
            'kernel.name'             => 'kernel',
            'kernel.root_dir'         => __DIR__,
        )));
    }
}
