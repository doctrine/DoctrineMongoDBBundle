<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\TypeProviderPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\BasicFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\ComplexFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\DisabledFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types\CustomTypeService;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types\CustomTypeWithoutName;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types\CustomTypeWithTag;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types\CustomTypeWithTagAndDefaultManager;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types\CustomTypeWithTagAndOtherManager;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types\MoneyType;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Types18\CustomTypeWithDependency;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Value\Money;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Value\MoneyDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Types\TypeProviderGlobalFallback;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeProvider;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use MongoDB\Client;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
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
use function sprintf;

abstract class AbstractMongoDBExtensionTestCase extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, string $file): void;

    public function testDependencyInjectionConfigurationDefaults(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();

        $loader->load(DoctrineMongoDBExtensionTest::buildConfiguration(), $container);

        $this->assertEquals('MongoDBODMProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(
            self::useNativeLazyObject() ? Configuration::AUTOGENERATE_NEVER : Configuration::AUTOGENERATE_EVAL,
            $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'),
        );

        $config = DoctrineMongoDBExtensionTest::buildConfiguration([
            'proxy_namespace' => 'MyProxies',
            'auto_generate_proxy_classes' => true,
            'connections' => ['default' => []],
            'document_managers' => ['default' => []],
        ]);
        $loader->load($config, $container);

        $this->assertEquals('MyProxies', $container->getParameter('doctrine_mongodb.odm.proxy_namespace'));
        $this->assertEquals(! self::useNativeLazyObject(), $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));

        (new ResolveChildDefinitionsPass())->process($container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals(Client::class, $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals(null, $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals(DocumentManager::class, $definition->getClass());
        $this->assertEquals([DocumentManager::class, 'create'], $definition->getFactory());
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

        (new ResolveChildDefinitionsPass())->process($container);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals(Client::class, $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);
        $this->assertArrayHasKey('context', $arguments[2]);
        $this->assertEquals(new Reference('my_context'), $arguments[2]['context']);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals(DocumentManager::class, $definition->getClass());
        $this->assertEquals([DocumentManager::class, 'create'], $definition->getFactory());
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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals(Client::class, $definition->getClass());

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
        $this->assertEquals(DocumentManager::class, $definition->getClass());
        $this->assertEquals([DocumentManager::class, 'create'], $definition->getFactory());
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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $this->assertEquals(Client::class, $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $this->assertEquals(DocumentManager::class, $definition->getClass());
        $this->assertEquals([DocumentManager::class, 'create'], $definition->getFactory());
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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn1_connection');
        $this->assertEquals(Client::class, $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm1_document_manager');
        $this->assertEquals(DocumentManager::class, $definition->getClass());
        $this->assertEquals([DocumentManager::class, 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.conn1_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.dm1_configuration', (string) $arguments[1]);

        $definition = $container->getDefinition('doctrine_mongodb.odm.conn2_connection');
        $this->assertEquals(Client::class, $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertEquals('mongodb://localhost:27017', $arguments[0]);
        $this->assertEquals([], $arguments[1]);
        $this->assertArrayHasKey('typeMap', $arguments[2]);
        $this->assertSame(['root' => 'array', 'document' => 'array'], $arguments[2]['typeMap']);

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm2_document_manager');
        $this->assertEquals(DocumentManager::class, $definition->getClass());
        $this->assertEquals([DocumentManager::class, 'create'], $definition->getFactory());
        $this->assertArrayHasKey('doctrine_mongodb.odm.document_manager', $definition->getTags());

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection', (string) $arguments[0]);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertEquals('doctrine_mongodb.odm.dm2_configuration', (string) $arguments[1]);

        $this->assertEquals('doctrine_mongodb.odm.dm2_document_manager', (string) $container->getAlias('doctrine_mongodb.odm.document_manager'));
        $this->assertEquals('doctrine_mongodb.odm.conn2_connection.event_manager', (string) $container->getAlias('doctrine_mongodb.odm.event_manager'));
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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm1_metadata_cache');
        $this->assertEquals(ArrayAdapter::class, $definition->getClass());

        $definition = $container->getDefinition('doctrine_mongodb.odm.dm2_metadata_cache');
        $this->assertEquals(ApcuAdapter::class, $definition->getClass());
    }

    public function testDocumentManagerMemcachedMetadataCacheDriverConfiguration(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'mongodb_service_simple_single_connection');

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $definition = $container->getDefinition('doctrine_mongodb.odm.default_metadata_cache');
        $this->assertEquals(MemcachedAdapter::class, $definition->getClass());

        $args = $definition->getArguments();
        $this->assertEquals('doctrine_mongodb.odm.default_memcached_instance', (string) $args[0]);

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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $this->assertSame(self::useNativeLazyObject(), ! $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes'));
    }

    public function testResolveTargetDocument(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_resolve_target_document');

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
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

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        $expected = [
            'custom_type_shortcut' => ['class' => 'Vendor\Type\CustomTypeShortcut'],
            'custom_type' => ['class' => 'Vendor\Type\CustomType'],
        ];

        $definition = $container->getDefinition('doctrine_mongodb.odm.manager_configurator.abstract');
        $this->assertDefinitionMethodCallAny($definition, 'loadTypes', [$expected]);

        // Class-only types keep using the legacy global Type::registerType() path (via the
        // manager configurator), so no scoped per-manager TypeProvider is introduced or injected.
        $this->assertFalse($container->has('doctrine_mongodb.odm.default_type_provider'));
        $configuration = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        foreach ($configuration->getMethodCalls() as $methodCall) {
            $this->assertNotSame('setTypeProvider', $methodCall[0], 'Class-only config types must not inject a scoped TypeRegistry into the manager configuration.');
        }
    }

    public function testNoCustomTypesUsesGlobalRegistry(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        $this->loadFromFile($container, 'odm_no_types');

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        // Without any configured or tagged type, no global loadTypes() call is emitted and no
        // scoped TypeRegistry exists: the manager keeps the shared/global registry untouched.
        $definition = $container->getDefinition('doctrine_mongodb.odm.manager_configurator.abstract');
        foreach ($definition->getMethodCalls() as $methodCall) {
            $this->assertNotSame('loadTypes', $methodCall[0], 'Without custom types, loadTypes() must not be called.');
        }

        $this->assertFalse($container->has('doctrine_mongodb.odm.default_type_provider'));
        $configuration = $container->getDefinition('doctrine_mongodb.odm.default_configuration');
        foreach ($configuration->getMethodCalls() as $methodCall) {
            $this->assertNotSame('setTypeProvider', $methodCall[0], 'Without custom types, no scoped TypeRegistry must be injected.');
        }
    }

    public function testCustomTypesService(): void
    {
        $container = $this->getContainer();
        $loader    = new DoctrineMongoDBExtension();
        $container->registerExtension($loader);

        // Tagged services need the TypeRegistry and the type provider introduced in
        // doctrine/mongodb-odm 2.18.
        if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.18@dev')) {
            self::expectException(LogicException::class);
            self::expectExceptionMessage('MongoDB field types services not supported. Upgrade to doctrine/mongodb-odm >= 2.18');
        }

        $this->loadFromFile($container, 'odm_types_service');
        $container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $container->addCompilerPass(new TypeProviderPass());
        $container->compile();

        // TypeProviderPass creates a per-manager service backed by a service locator and injects
        // it on the manager Configuration via setTypeProvider(). Resolving the configured types
        // and the tagged #[AsFieldType] services below is the authoritative proof that the scoped
        // registry is in effect: the shared global registry cannot resolve them, since the tagged
        // ones exist only as services in this container.
        $typeProvider = $this->getTypeProvider($container, 'default');
        self::assertInstanceOf(TypeProviderGlobalFallback::class, $typeProvider);

        $this->assertTypeRegistered($typeProvider, 'custom_type_shortcut', CustomTypeService::class);
        $this->assertTypeRegistered($typeProvider, 'custom_type', CustomTypeService::class);
        $this->assertTypeRegistered($typeProvider, 'manual_tag_type', CustomTypeService::class);
        $this->assertTypeRegistered($typeProvider, 'custom_type_with_tag', CustomTypeWithTag::class);
        $this->assertTypeRegistered($typeProvider, 'custom_type_with_tag_and_default_manager', CustomTypeWithTagAndDefaultManager::class);
        $this->assertTypeRegistered($typeProvider, Money::class, MoneyType::class);
        $this->assertTypeNotRegistered($typeProvider, 'custom_type_with_tag_and_other_manager');

        // A type tagged for another document manager is not registered on the default one and
        // the other way around.
        $otherTypeProvider = $this->getTypeProvider($container, 'other');

        $this->assertTypeRegistered($otherTypeProvider, 'custom_type', CustomTypeService::class);
        $this->assertTypeRegistered($otherTypeProvider, 'manual_tag_type', CustomTypeService::class);
        $this->assertTypeRegistered($otherTypeProvider, 'custom_type_with_tag', CustomTypeWithTag::class);
        $this->assertTypeRegistered($otherTypeProvider, 'custom_type_with_tag_and_other_manager', CustomTypeWithTagAndOtherManager::class);
        $this->assertTypeNotRegistered($otherTypeProvider, 'custom_type_with_tag_and_default_manager');
    }

    public function testTypeServiceReceivesInjectedDependency(): void
    {
        $this->requiresTypeProvider();

        $container = $this->createContainerWithTypes();
        $container->register('app.mongodb.type_with_dependency', CustomTypeWithDependency::class)
            ->addArgument('injected')
            ->addTag('doctrine_mongodb.odm.field_type', ['type' => 'custom_type_with_dependency']);
        $container->compile();

        $type = $this->getType($this->getTypeProvider($container, 'default'), 'custom_type_with_dependency');

        self::assertInstanceOf(CustomTypeWithDependency::class, $type);
        self::assertSame('injected', $type->dependency);
    }

    public function testAsFieldTypeWithoutNameUsesTheTypeClassAsName(): void
    {
        $this->requiresTypeProvider();

        $typeProvider = $this->getTypeProvider($this->compileContainerWithTypes(), 'default');

        $this->assertTypeRegistered($typeProvider, CustomTypeWithoutName::class, CustomTypeWithoutName::class);
    }

    public function testTypeIsInferredFromThePropertyType(): void
    {
        $this->requiresTypeProvider();

        $typeProvider = $this->getTypeProvider($this->compileContainerWithTypes(), 'default');

        $classMetadata = new ClassMetadata(MoneyDocument::class);
        $classMetadata->setTypeProvider($typeProvider);
        $classMetadata->mapField(['fieldName' => 'price']);

        self::assertSame(Money::class, $classMetadata->getTypeOfField('price'));

        $type = $this->getType($typeProvider, Money::class);
        self::assertSame(1500, $type->convertToDatabaseValue(new Money(1500)));
        self::assertInstanceOf(Money::class, $type->convertToPHPValue(1500));
    }

    public function testGloballyRegisteredTypesStayAvailable(): void
    {
        $this->requiresTypeProvider();

        // A type registered by other code with the static API, like a third-party bundle does.
        TypeRegistry::getDeprecatedSharedInstance()->register('global_fallback_type', CustomTypeService::class);

        $typeProvider = $this->getTypeProvider($this->compileContainerWithTypes(), 'default');

        // The document manager still falls back to the global registry for the types that are
        // not registered as services.
        $this->assertTypeRegistered($typeProvider, 'global_fallback_type', CustomTypeService::class);
        $this->assertTypeRegistered($typeProvider, 'custom_type_with_tag', CustomTypeWithTag::class);
        $this->assertTypeNotRegistered($typeProvider, 'custom_type_with_tag_and_other_manager');
    }

    public function testGlobalOverridesOfBuiltinTypesStayAvailable(): void
    {
        $this->requiresTypeProvider();

        $registry      = TypeRegistry::getDeprecatedSharedInstance();
        $originalClass = $registry->get(Type::VECTOR_PACKED_BIT)::class;

        // Overrides a built-in type in the global registry, like Type::overrideType() does.
        $registry->register(Type::VECTOR_PACKED_BIT, CustomTypeService::class);

        try {
            $typeProvider = $this->getTypeProvider($this->compileContainerWithTypes(), 'default');

            $this->assertTypeRegistered($typeProvider, Type::VECTOR_PACKED_BIT, CustomTypeService::class);
        } finally {
            $registry->register(Type::VECTOR_PACKED_BIT, $originalClass);
        }
    }

    public function testTagWithoutTypeParameterThrows(): void
    {
        $this->requiresTypeProvider();

        $container = $this->createContainerWithTypes();
        $container->register('app.type_without_name', CustomTypeService::class)
            ->addTag('doctrine_mongodb.odm.field_type');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('must define the "type" parameter');

        $container->compile();
    }

    public function testUnknownDocumentManagerThrows(): void
    {
        $this->requiresTypeProvider();

        $container = $this->createContainerWithTypes();
        $container->register('app.type_with_unknown_manager', CustomTypeService::class)
            ->addTag('doctrine_mongodb.odm.field_type', ['type' => 'unknown_manager_type', 'document_manager' => 'unknown']);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('unknown document manager "unknown"');

        $container->compile();
    }

    public function testTaggedServiceMustExtendType(): void
    {
        $this->requiresTypeProvider();

        $container = $this->createContainerWithTypes();
        $container->register('app.not_a_type', 'stdClass')
            ->addTag('doctrine_mongodb.odm.field_type', ['type' => 'not_a_type']);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('must extend');

        $container->compile();
    }

    public function testTaggedServiceClassCanBeAParameter(): void
    {
        $this->requiresTypeProvider();

        $container = $this->createContainerWithTypes();
        $container->setParameter('app.type_class', CustomTypeService::class);
        $container->register('app.parameterized_type')
            ->setClass('%app.type_class%')
            ->addTag('doctrine_mongodb.odm.field_type', ['type' => 'parameterized_type']);
        $container->compile();

        $this->assertTypeRegistered($this->getTypeProvider($container, 'default'), 'parameterized_type', CustomTypeService::class);
    }

    public function testTagAttributesCanBeParameters(): void
    {
        $this->requiresTypeProvider();

        $container = $this->createContainerWithTypes();
        $container->setParameter('app.type_name', 'parameterized_name');
        $container->setParameter('app.manager_name', 'other');
        $container->register('app.parameterized_tag', CustomTypeService::class)
            ->addTag('doctrine_mongodb.odm.field_type', ['type' => '%app.type_name%', 'document_manager' => '%app.manager_name%']);
        $container->compile();

        $this->assertTypeRegistered($this->getTypeProvider($container, 'other'), 'parameterized_name', CustomTypeService::class);
        $this->assertTypeNotRegistered($this->getTypeProvider($container, 'default'), 'parameterized_name');
    }

    private function requiresTypeProvider(): void
    {
        if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/mongodb-odm', '>=2.18@dev')) {
            self::markTestSkipped('These tests require the type provider introduced in doctrine/mongodb-odm 2.18.');
        }
    }

    /**
     * Creates the container for the "types as services" fixtures, without compiling it.
     */
    private function createContainerWithTypes(): ContainerBuilder
    {
        $container = $this->getContainer();
        $container->registerExtension(new DoctrineMongoDBExtension());
        $this->loadFromFile($container, 'odm_types_service');
        $container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $container->addCompilerPass(new TypeProviderPass());

        return $container;
    }

    private function compileContainerWithTypes(): ContainerBuilder
    {
        $container = $this->createContainerWithTypes();
        $container->compile();

        return $container;
    }

    /**
     * Returns the field type provider of the given document manager.
     *
     * The type provider is only available from doctrine/mongodb-odm 2.18.
     */
    private function getTypeProvider(ContainerBuilder $container, string $managerName): TypeProvider
    {
        $documentManager = $container->get(sprintf('doctrine_mongodb.odm.%s_document_manager', $managerName));
        self::assertInstanceOf(DocumentManager::class, $documentManager);

        return $documentManager->getConfiguration()->getTypeProvider();
    }

    /**
     * Returns the type with the given name from the given type provider.
     */
    private function getType(object $typeProvider, string $name): Type
    {
        // @phpstan-ignore method.notFound
        return $typeProvider->get($name);
    }

    /**
     * Asserts that the given type provider resolves a field type.
     *
     * @param class-string $class
     */
    private function assertTypeRegistered(object $typeProvider, string $name, string $class): void
    {
        // @phpstan-ignore method.notFound
        self::assertTrue($typeProvider->has($name));
        // @phpstan-ignore method.notFound
        self::assertInstanceOf($class, $typeProvider->get($name));
    }

    /**
     * Asserts that the given type provider does not resolve a field type.
     */
    private function assertTypeNotRegistered(object $typeProvider, string $name): void
    {
        // @phpstan-ignore method.notFound
        self::assertFalse($typeProvider->has($name));
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
