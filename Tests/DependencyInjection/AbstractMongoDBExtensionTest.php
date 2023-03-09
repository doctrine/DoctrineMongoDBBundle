<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\BasicFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\ComplexFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\DisabledFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use MongoDB\Client;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bridge\Doctrine\SchemaListener\DoctrineDbalCacheAdapterSchemaSubscriber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_map;
use function array_search;
use function class_exists;
use function class_implements;
use function in_array;
use function is_dir;
use function reset;

abstract class AbstractMongoDBExtensionTest extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, string $file): void;

    /** @psalm-suppress UndefinedClass this won't be necessary when removing metadata cache configuration */
    public function testDependencyInjectionConfigurationDefaults(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();

        $loader->load(DoctrineMongoDBExtensionTest::buildConfiguration(), $container);

        $this->assertEquals(Client::class, $container->getParameter('doctrine_mongodb.odm.connection.class'));
        $this->assertEquals(Configuration::class, $container->getParameter('doctrine_mongodb.odm.configuration.class'));
        $this->assertEquals(DocumentManager::class, $container->getParameter('doctrine_mongodb.odm.document_manager.class'));
        $this->assertEquals('MongoDBODMProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(Configuration::AUTOGENERATE_EVAL, $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
        $this->assertEquals(ArrayCache::class, $container->getParameter('doctrine_mongodb.odm.cache.array.class'));
        $this->assertEquals(ApcCache::class, $container->getParameter('doctrine_mongodb.odm.cache.apc.class'));
        $this->assertEquals(MemcacheCache::class, $container->getParameter('doctrine_mongodb.odm.cache.memcache.class'));
        $this->assertEquals('localhost', $container->getParameter('doctrine_mongodb.odm.cache.memcache_host'));
        $this->assertEquals('11211', $container->getParameter('doctrine_mongodb.odm.cache.memcache_port'));
        $this->assertEquals('Memcache', $container->getParameter('doctrine_mongodb.odm.cache.memcache_instance.class'));
        $this->assertEquals(XcacheCache::class, $container->getParameter('doctrine_mongodb.odm.cache.xcache.class'));
        $this->assertEquals(MappingDriverChain::class, $container->getParameter('doctrine_mongodb.odm.metadata.driver_chain.class'));
        $this->assertEquals(AnnotationDriver::class, $container->getParameter('doctrine_mongodb.odm.metadata.annotation.class'));
        $this->assertEquals(AttributeDriver::class, $container->getParameter('doctrine_mongodb.odm.metadata.attribute.class'));
        $this->assertEquals(XmlDriver::class, $container->getParameter('doctrine_mongodb.odm.metadata.xml.class'));

        $this->assertEquals(UniqueEntityValidator::class, $container->getParameter('doctrine_odm.mongodb.validator.unique.class'));

        $config = DoctrineMongoDBExtensionTest::buildConfiguration([
            'proxy_namespace' => 'MyProxies',
            'auto_generate_proxy_classes' => true,
            'connections' => ['default' => []],
            'document_managers' => ['default' => []],
        ]);
        $loader->load($config, $container);

        $this->assertEquals('MyProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(true, $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals(null, $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

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

    public function testSingleDocumentManagerConfiguration(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();

        $config = [
            'connections' => [
                'default' => [
                    'server' => 'mongodb://localhost:27017',
                    'options' => [],
                    'driver_options' => ['context' => 'my_context'],
                ],
            ],
            'document_managers' => ['default' => []],
        ];
        $loader->load([$config], $container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);
        $this->assertArrayHasKey('context', $arguments[2]);
        $this->assertEquals(new Reference('my_context'), $arguments[2]['context']);

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

    public function testLoadSimpleSingleConnection(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_simple_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

        $definition  = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $methodCalls = $definition->getMethodCalls();
        $methodNames = array_map(static fn ($call) => $call[0], $methodCalls);
        $this->assertIsInt($pos = array_search('setDefaultDB', $methodNames));
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

    public function testLoadSingleConnection(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

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

    public function testLoadMultipleConnections(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_multiple_connections');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn1_connection');
        $this->assertEquals('%doctrine_mongodb.odm.connection.class%', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

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
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

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

    public function testBundleDocumentAliases(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();

        $config = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['XmlBundle' => []]]]],
        );
        $loader->load($config, $container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        $calls      = $definition->getMethodCalls();
        $this->assertTrue(isset($calls[0][1][0]['XmlBundle']));
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document', $calls[0][1][0]['XmlBundle']);
    }

    public function testXmlBundleMappingDetection(): void
    {
        $container = $this->getContainer('XmlBundle');
        $loader    = new DoctrineMongoDBExtension();
        $config    = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['XmlBundle' => []]]]],
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_xml_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document', $calls[0][1][1]);
    }

    public function testNewBundleStructureXmlBundleMappingDetection(): void
    {
        if (! class_exists(DoctrineDbalCacheAdapterSchemaSubscriber::class)) {
            $this->markTestSkipped('Test requires symfony/doctrine-bridge >=5.4');
        }

        $container = $this->getContainer('NewXmlBundle');
        $loader    = new DoctrineMongoDBExtension();
        $config    = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['NewXmlBundle' => []]]]],
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_xml_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\NewXmlBundle\Document', $calls[0][1][1]);
    }

    public function testAnnotationsBundleMappingDetection(): void
    {
        $container = $this->getContainer('AnnotationsBundle');
        $loader    = new DoctrineMongoDBExtension();
        $config    = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['AnnotationsBundle' => []]]]],
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_annotation_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\AnnotationsBundle\Document', $calls[0][1][1]);
    }

    /** @requires PHP 8.0 */
    public function testAttributesBundleMappingDetection(): void
    {
        if (! class_exists(AttributeDriver::class)) {
            self::markTestSkipped('This test requires MongoDB ODM 2.3 with attribute driver.');
        }

        $container = $this->getContainer('AttributesBundle');
        $loader    = new DoctrineMongoDBExtension();
        $config    = DoctrineMongoDBExtensionTest::buildConfiguration(
            ['document_managers' => ['default' => ['mappings' => ['AttributesBundle' => 'attribute']]]],
        );
        $loader->load($config, $container);

        $calls = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver')->getMethodCalls();
        $this->assertEquals('doctrine_mongodb.odm.default_attribute_metadata_driver', (string) $calls[0][1][0]);
        $this->assertEquals('Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\AttributesBundle\Document', $calls[0][1][1]);
    }

    public function testDocumentManagerMetadataCacheDriverConfiguration(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
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

    /** @psalm-suppress UndefinedClass this won't be necessary when removing metadata cache configuration */
    public function testDocumentManagerMemcachedMetadataCacheDriverConfiguration(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_simple_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_metadata_cache');
        $this->assertEquals(MemcachedCache::class, $definition->getClass());

        $calls = $definition->getMethodCalls();
        $this->assertEquals('setMemcached', $calls[0][0]);
        $this->assertEquals('doctrine_mongodb.odm.default_memcached_instance', (string) $calls[0][1][0]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_memcached_instance');
        $this->assertEquals('Memcached', $definition->getClass());

        $calls = $definition->getMethodCalls();
        $this->assertEquals('addServer', $calls[0][0]);
        $this->assertEquals('localhost', $calls[0][1][0]);
        $this->assertEquals(11211, $calls[0][1][1]);
    }

    public function testDependencyInjectionImportsOverrideDefaults(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);
        $config = DoctrineMongoDBExtensionTest::buildConfiguration();
        $container->prependExtensionConfig($loader->getAlias(), reset($config));

        $this->loadFromFile($container, 'odm_imports');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $this->assertTrue((bool) $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
    }

    public function testResolveTargetDocument(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_resolve_target_document');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.listeners.resolve_target_document');
        $this->assertDefinitionMethodCallOnce($definition, 'addResolveTargetDocument', [UserInterface::class, 'MyUserClass', []]);

        if (in_array(EventSubscriber::class, class_implements($container->getParameterBag()->resolveValue($definition->getClass())))) {
            $this->assertEquals([[]], $definition->getTags()['doctrine_mongodb.odm.event_subscriber']);
        } else {
            $this->assertEquals([['event' => 'loadClassMetadata']], $definition->getTags()['doctrine_mongodb.odm.event_listener']);
        }
    }

    public function testFilters(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
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
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', ['disabled_filter', DisabledFilter::class, []]);
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', ['basic_filter', BasicFilter::class, []]);
        $this->assertDefinitionMethodCallAny($definition, 'addFilter', ['complex_filter', ComplexFilter::class, $complexParameters]);

        $enabledFilters = ['basic_filter', 'complex_filter'];

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_manager_configurator');
        $this->assertEquals($enabledFilters, $definition->getArgument(0), 'Only enabled filters are passed to the ManagerConfigurator.');
    }

    public function testCustomTypes(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_types');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $expected = [
            'custom_type_shortcut' => ['class' => 'Vendor\Type\CustomTypeShortcut'],
            'custom_type' => ['class' => 'Vendor\Type\CustomType'],
        ];

        $definition = $container->getDefinition('doctrine_mongodb.odm.manager_configurator.abstract');
        $this->assertDefinitionMethodCallAny($definition, 'loadTypes', [$expected]);
    }

    /**
     * Asserts that the given definition contains a call to the method that uses
     * the specified parameters.
     *
     * @param mixed[] $params
     */
    private function assertDefinitionMethodCallAny(Definition $definition, string $methodName, array $params): void
    {
        $calls     = $definition->getMethodCalls();
        $called    = false;
        $lastError = null;

        foreach ($calls as $call) {
            if ($call[0] !== $methodName) {
                continue;
            }

            $called = true;

            try {
                $this->assertSame($params, $call[1], "Expected parameters to method '" . $methodName . "' did not match the actual parameters.");

                return;
            } catch (AssertionFailedError $e) {
                $lastError = $e;
            }
        }

        if (! $called) {
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
     * @param mixed[] $params
     */
    private function assertDefinitionMethodCallOnce(Definition $definition, string $methodName, array $params): void
    {
        $calls  = $definition->getMethodCalls();
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

        if ($called) {
            return;
        }

        $this->fail("Method '" . $methodName . "' is expected to be called once, but it was never called.");
    }

    protected function getContainer(string $bundle = 'XmlBundle'): ContainerBuilder
    {
        $bundleDir = __DIR__ . '/Fixtures/Bundles/' . $bundle;

        if (is_dir($bundleDir . '/src')) {
            require_once $bundleDir . '/src/' . $bundle . '.php';
        } else {
            require_once $bundleDir . '/' . $bundle . '.php';
        }

        return new ContainerBuilder(new ParameterBag([
            'kernel.bundles'          => [$bundle => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\' . $bundle . '\\' . $bundle],
            'kernel.bundles_metadata' => [$bundle => ['path' => $bundleDir, 'namespace' => 'Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\' . $bundle]],
            'kernel.cache_dir'        => __DIR__,
            'kernel.compiled_classes' => [],
            'kernel.debug'            => false,
            'kernel.environment'      => 'test',
            'kernel.name'             => 'kernel',
            'kernel.root_dir'         => __DIR__,
            'kernel.project_dir'      => __DIR__,
            'kernel.container_class'  => Container::class,
        ]));
    }
}
