<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection;

use Composer\InstalledVersions;
use Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener;
use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\FixturesCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepositoryInterface;
use Doctrine\Common\DataFixtures\Loader as DataFixturesLoader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Configuration as ODMConfiguration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;
use Doctrine\ODM\MongoDB\Mapping\Annotations\File;
use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\MongoDB\Mapping\Annotations\QueryResultDocument;
use Doctrine\ODM\MongoDB\Mapping\Annotations\View;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Proxy;
use InvalidArgumentException;
use MongoDB\Client;
use ProxyManager\Proxy\LazyLoadingInterface;
use Symfony\Bridge\Doctrine\DependencyInjection\AbstractDoctrineExtension;
use Symfony\Bridge\Doctrine\Messenger\DoctrineClearEntityManagerWorkerSubscriber;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

use function array_key_first;
use function array_merge;
use function class_exists;
use function class_implements;
use function in_array;
use function interface_exists;
use function is_dir;
use function method_exists;
use function sprintf;

/**
 * Doctrine MongoDB ODM extension.
 */
class DoctrineMongoDBExtension extends AbstractDoctrineExtension
{
    private static ?string $odmVersion = null;

    /** @internal */
    public const CONFIGURATION_TAG = 'doctrine.odm.configuration';

    /**
     * Responds to the doctrine_mongodb configuration parameter.
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader->load('mongodb.php');
        $loader->load('cache_warmer.php');
        $loader->load('command.php');
        $loader->load('form.php');
        $loader->load('logger.php');
        $loader->load('profiler.php');
        $loader->load('validator.php');

        if (empty($config['default_connection'])) {
            $config['default_connection'] = array_key_first($config['connections']);
        }

        $container->setParameter('doctrine_mongodb.odm.default_connection', $config['default_connection']);

        if (empty($config['default_document_manager'])) {
            $config['default_document_manager'] = array_key_first($config['document_managers']);
        }

        $container->setParameter('doctrine_mongodb.odm.default_document_manager', $config['default_document_manager']);

        if (! empty($config['types'])) {
            $configuratorDefinition = $container->getDefinition('doctrine_mongodb.odm.manager_configurator.abstract');
            $configuratorDefinition->addMethodCall('loadTypes', [$config['types']]);
        }

        // set some options as parameters and unset them
        $config = $this->overrideParameters($config, $container);

        if (class_exists(DataFixturesLoader::class)) {
            // Autowiring fixture loader
            $container->registerForAutoconfiguration(ODMFixtureInterface::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);
        } else {
            $container->removeDefinition('doctrine_mongodb.odm.symfony.fixtures.loader');
            $container->removeDefinition('doctrine_mongodb.odm.command.load_data_fixtures');
        }

        // Requires doctrine/mongodb-odm 2.10
        $container->getDefinition('doctrine_mongodb')
            ->setArgument(5, $config['enable_lazy_ghost_objects'] ? Proxy::class : LazyLoadingInterface::class);

        // load the connections
        $this->loadConnections($config['connections'], $container);

        $config['document_managers'] = $this->fixManagersAutoMappings($config['document_managers'], $container->getParameter('kernel.bundles'));

        // load the document managers
        $this->loadDocumentManagers(
            $config['document_managers'],
            $config['default_document_manager'],
            $config['default_database'],
            $container,
            $config['enable_lazy_ghost_objects'],
        );

        if ($config['resolve_target_documents']) {
            $def = $container->findDefinition('doctrine_mongodb.odm.listeners.resolve_target_document');
            foreach ($config['resolve_target_documents'] as $name => $implementation) {
                $def->addMethodCall('addResolveTargetDocument', [$name, $implementation, []]);
            }

            // Register service has an event subscriber if implement interface
            if (in_array(EventSubscriber::class, class_implements($container->getParameterBag()->resolveValue($def->getClass())))) {
                $def->addTag('doctrine_mongodb.odm.event_subscriber');
            } else {
                $def->addTag('doctrine_mongodb.odm.event_listener', ['event' => 'loadClassMetadata']);
            }
        }

        $container->registerForAutoconfiguration(ServiceDocumentRepositoryInterface::class)
            ->addTag(ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

        $container->registerAttributeForAutoconfiguration(AsDocumentListener::class, static function (ChildDefinition $definition, AsDocumentListener $attribute): void {
            $definition->addTag('doctrine_mongodb.odm.event_listener', [
                'event'      => $attribute->event,
                'connection' => $attribute->connection,
                'priority'   => $attribute->priority,
            ]);
        });

        // Document classes are excluded from the container by default
        $container->registerAttributeForAutoconfiguration(Document::class, static function (ChildDefinition $definition): void {
            $definition->addTag('container.excluded', ['source' => sprintf('with #[%s] attribute', Document::class)]);
        });
        $container->registerAttributeForAutoconfiguration(EmbeddedDocument::class, static function (ChildDefinition $definition): void {
            $definition->addTag('container.excluded', ['source' => sprintf('with #[%s] attribute', EmbeddedDocument::class)]);
        });
        $container->registerAttributeForAutoconfiguration(MappedSuperclass::class, static function (ChildDefinition $definition): void {
            $definition->addTag('container.excluded', ['source' => sprintf('with #[%s] attribute', MappedSuperclass::class)]);
        });
        $container->registerAttributeForAutoconfiguration(View::class, static function (ChildDefinition $definition): void {
            $definition->addTag('container.excluded', ['source' => sprintf('with #[%s] attribute', View::class)]);
        });
        $container->registerAttributeForAutoconfiguration(QueryResultDocument::class, static function (ChildDefinition $definition): void {
            $definition->addTag('container.excluded', ['source' => sprintf('with #[%s] attribute', QueryResultDocument::class)]);
        });
        $container->registerAttributeForAutoconfiguration(File::class, static function (ChildDefinition $definition): void {
            $definition->addTag('container.excluded', ['source' => sprintf('with #[%s] attribute', File::class)]);
        });

        $this->loadMessengerServices($container, $loader);

        $this->loadEntityValueResolverServices($container, $loader, $config);
    }

    /**
     * Uses some of the extension options to override DI extension parameters.
     *
     * @param array            $options   The available configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return array<string, mixed>
     */
    protected function overrideParameters(array $options, ContainerBuilder $container): array
    {
        $overrides = [
            'proxy_namespace',
            'proxy_dir',
            'auto_generate_proxy_classes',
            'hydrator_namespace',
            'hydrator_dir',
            'auto_generate_hydrator_classes',
            'default_commit_options',
            'persistent_collection_dir',
            'persistent_collection_namespace',
            'auto_generate_persistent_collection_classes',
        ];

        foreach ($overrides as $key) {
            if (! isset($options[$key])) {
                continue;
            }

            $container->setParameter('doctrine_mongodb.odm.' . $key, $options[$key]);

            // the option should not be used, the parameter should be referenced
            unset($options[$key]);
        }

        return $options;
    }

    /**
     * Loads the document managers configuration.
     *
     * @param array            $dmConfigs An array of document manager configs
     * @param string|null      $defaultDM The default document manager name
     * @param string           $defaultDB The default db name
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManagers(array $dmConfigs, string|null $defaultDM, string $defaultDB, ContainerBuilder $container, bool $useLazyGhostObject = false): void
    {
        $dms = [];
        foreach ($dmConfigs as $name => $documentManager) {
            $documentManager['name'] = $name;
            $this->loadDocumentManager(
                $documentManager,
                $defaultDM,
                $defaultDB,
                $container,
                $useLazyGhostObject,
            );
            $dms[$name] = sprintf('doctrine_mongodb.odm.%s_document_manager', $name);
        }

        $container->setParameter('doctrine_mongodb.odm.document_managers', $dms);
    }

    /**
     * Loads a document manager configuration.
     *
     * @param array            $documentManager A document manager configuration array
     * @param string|null      $defaultDM       The default document manager name
     * @param string           $defaultDB       The default db name
     * @param ContainerBuilder $container       A ContainerBuilder instance
     */
    protected function loadDocumentManager(array $documentManager, string|null $defaultDM, string $defaultDB, ContainerBuilder $container, bool $useLazyGhostObject = false): void
    {
        $connectionName  = $documentManager['connection'] ?? $documentManager['name'];
        $configurationId = sprintf('doctrine_mongodb.odm.%s_configuration', $documentManager['name']);
        $defaultDatabase = $documentManager['database'] ?? $defaultDB;

        $odmConfigDef = new Definition(ODMConfiguration::class);
        $odmConfigDef->addTag(self::CONFIGURATION_TAG);
        $container->setDefinition(
            $configurationId,
            $odmConfigDef,
        );

        $this->loadDocumentManagerBundlesMappingInformation($documentManager, $odmConfigDef, $container);
        $this->loadObjectManagerCacheDriver($documentManager, $container, 'metadata_cache');

        $methods = [
            'setMetadataCache' => new Reference(sprintf('doctrine_mongodb.odm.%s_metadata_cache', $documentManager['name'])),
            'setMetadataDriverImpl' => new Reference(sprintf('doctrine_mongodb.odm.%s_metadata_driver', $documentManager['name'])),
            'setProxyDir' => '%doctrine_mongodb.odm.proxy_dir%',
            'setProxyNamespace' => '%doctrine_mongodb.odm.proxy_namespace%',
            'setAutoGenerateProxyClasses' => '%doctrine_mongodb.odm.auto_generate_proxy_classes%',
            'setHydratorDir' => '%doctrine_mongodb.odm.hydrator_dir%',
            'setHydratorNamespace' => '%doctrine_mongodb.odm.hydrator_namespace%',
            'setAutoGenerateHydratorClasses' => '%doctrine_mongodb.odm.auto_generate_hydrator_classes%',
            'setDefaultDB' => $defaultDatabase,
            'setDefaultCommitOptions' => '%doctrine_mongodb.odm.default_commit_options%',
            'setDefaultDocumentRepositoryClassName' => $documentManager['default_document_repository_class'],
            'setDefaultGridFSRepositoryClassName' => $documentManager['default_gridfs_repository_class'],
            'setPersistentCollectionDir' => '%doctrine_mongodb.odm.persistent_collection_dir%',
            'setPersistentCollectionNamespace' => '%doctrine_mongodb.odm.persistent_collection_namespace%',
            'setAutoGeneratePersistentCollectionClasses' => '%doctrine_mongodb.odm.auto_generate_persistent_collection_classes%',
        ];

        if ($useLazyGhostObject) {
            $methods['setUseLazyGhostObject'] = $useLazyGhostObject;
        }

        if (method_exists(ODMConfiguration::class, 'setUseTransactionalFlush')) {
            $methods['setUseTransactionalFlush'] = $documentManager['use_transactional_flush'];
        }

        if ($documentManager['repository_factory']) {
            $methods['setRepositoryFactory'] = new Reference($documentManager['repository_factory']);
        }

        if ($documentManager['persistent_collection_factory']) {
            $methods['setPersistentCollectionFactory'] = new Reference($documentManager['persistent_collection_factory']);
        }

        // logging
        if ($container->getParameterBag()->resolveValue($documentManager['logging'])) {
            $container->getDefinition('doctrine_mongodb.odm.psr_command_logger')
                ->addTag('doctrine_mongodb.odm.command_logger');
        }

        // profiler
        if ($container->getParameterBag()->resolveValue($documentManager['profiler']['enabled'])) {
            $container->getDefinition('doctrine_mongodb.odm.data_collector.command_logger')
                ->addTag('doctrine_mongodb.odm.command_logger');

            $container->getDefinition('doctrine_mongodb.odm.stopwatch_command_logger')
                ->addTag('doctrine_mongodb.odm.command_logger');

            $container
                ->getDefinition('doctrine_mongodb.odm.data_collector')
                ->addTag('data_collector', ['id' => 'mongodb', 'template' => '@DoctrineMongoDB/Collector/mongodb.html.twig']);
        }

        $enabledFilters = [];
        foreach ($documentManager['filters'] as $name => $filter) {
            $parameters = $filter['parameters'] ?? [];
            $odmConfigDef->addMethodCall('addFilter', [$name, $filter['class'], $parameters]);
            if (! $filter['enabled']) {
                continue;
            }

            $enabledFilters[] = $name;
        }

        $managerConfiguratorName = sprintf('doctrine_mongodb.odm.%s_manager_configurator', $documentManager['name']);

        $container
            ->setDefinition(
                $managerConfiguratorName,
                new ChildDefinition('doctrine_mongodb.odm.manager_configurator.abstract'),
            )
            ->replaceArgument(0, $enabledFilters);

        foreach ($methods as $method => $arg) {
            if ($odmConfigDef->hasMethodCall($method)) {
                $odmConfigDef->removeMethodCall($method);
            }

            $odmConfigDef->addMethodCall($method, [$arg]);
        }

        $odmDmArgs = [
            new Reference(sprintf('doctrine_mongodb.odm.%s_connection', $connectionName)),
            new Reference($configurationId),
            // Document managers will share their connection's event manager
            new Reference(sprintf('doctrine_mongodb.odm.%s_connection.event_manager', $connectionName)),
        ];
        $odmDmDef  = new Definition(DocumentManager::class, $odmDmArgs);
        $odmDmDef->setFactory([DocumentManager::class, 'create']);
        $odmDmDef->addTag('doctrine_mongodb.odm.document_manager');
        $odmDmDef->setPublic(true);

        $container
            ->setDefinition(sprintf('doctrine_mongodb.odm.%s_document_manager', $documentManager['name']), $odmDmDef)
            ->setConfigurator([new Reference($managerConfiguratorName), 'configure']);

        if ($documentManager['name'] !== $defaultDM) {
            return;
        }

        $container->setAlias(
            'doctrine_mongodb.odm.document_manager',
            new Alias(sprintf('doctrine_mongodb.odm.%s_document_manager', $documentManager['name'])),
        );
        $container->getAlias('doctrine_mongodb.odm.document_manager')->setPublic(true);

        $container->setAlias(
            'doctrine_mongodb.odm.event_manager',
            new Alias(sprintf('doctrine_mongodb.odm.%s_connection.event_manager', $connectionName)),
        );
    }

    /**
     * Loads the configured connections.
     *
     * @param array            $config    An array of connections configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadConnections(array $connections, ContainerBuilder $container): void
    {
        $cons = [];
        foreach ($connections as $name => $connection) {
            // Define an event manager for this connection
            $eventManagerId = sprintf('doctrine_mongodb.odm.%s_connection.event_manager', $name);
            $container->setDefinition(
                $eventManagerId,
                new ChildDefinition('doctrine_mongodb.odm.connection.event_manager'),
            );

            $configurationId = sprintf('doctrine_mongodb.odm.%s_configuration', $name);
            $container->setDefinition(
                $configurationId,
                new Definition(ODMConfiguration::class),
            );

            $odmConnArgs = [
                $connection['server'] ?? null,
                /* phpcs:ignore Squiz.Arrays.ArrayDeclaration.ValueNoNewline */
                $connection['options'] ?? [],
                $this->normalizeDriverOptions($connection),
            ];

            $odmConnDef = new Definition(Client::class, $odmConnArgs);
            $odmConnDef->setPublic(true);
            $id = sprintf('doctrine_mongodb.odm.%s_connection', $name);
            $container->setDefinition($id, $odmConnDef);
            $cons[$name] = $id;
        }

        $container->setParameter('doctrine_mongodb.odm.connections', $cons);
    }

    private function loadMessengerServices(ContainerBuilder $container, FileLoader $loader): void
    {
        if (! interface_exists(MessageBusInterface::class) || ! class_exists(DoctrineClearEntityManagerWorkerSubscriber::class)) {
            return;
        }

        $loader->load('messenger.php');
    }

    /** @param array<string, mixed> $config */
    private function loadEntityValueResolverServices(ContainerBuilder $container, FileLoader $loader, array $config): void
    {
        $loader->load('value_resolver.php');

        if (! class_exists(ExpressionLanguage::class)) {
            $container->removeDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language');
        }

        $controllerResolverDefaults = [];

        if (! $config['controller_resolver']['enabled']) {
            $controllerResolverDefaults['disabled'] = true;
        }

        if (! $config['controller_resolver']['auto_mapping']) {
            $controllerResolverDefaults['mapping'] = [];
        }

        if ($controllerResolverDefaults === []) {
            return;
        }

        $container->getDefinition('doctrine_mongodb.odm.entity_value_resolver')->setArgument(2, (new Definition(MapDocument::class))->setArguments([
            null,
            null,
            null,
            $controllerResolverDefaults['mapping'] ?? null,
            null,
            null,
            null,
            $controllerResolverDefaults['disabled'] ?? false,
        ]));
    }

    /**
     * Normalizes the driver options array
     *
     * @param array<string, mixed> $connection
     *
     * @return array<string, mixed>
     */
    private function normalizeDriverOptions(array $connection): array
    {
        $driverOptions            = $connection['driver_options'] ?? [];
        $driverOptions['typeMap'] = DocumentManager::CLIENT_TYPEMAP;

        if (isset($driverOptions['context'])) {
            $driverOptions['context'] = new Reference($driverOptions['context']);
        }

        $driverOptions['driver'] = [
            'name' => 'symfony-mongodb',
            'version' => self::getODMVersion(),
        ];

        return $driverOptions;
    }

    /**
     * Loads an ODM document managers bundle mapping information.
     *
     * There are two distinct configuration possibilities for mapping information:
     *
     * 1. Specify a bundle and optionally details where the entity and mapping information reside.
     * 2. Specify an arbitrary mapping location.
     *
     * @param array            $documentManager A configured ODM entity manager.
     * @param Definition       $odmConfigDef    A Definition instance
     * @param ContainerBuilder $container       A ContainerBuilder instance
     *
     * @example
     *
     *  doctrine_mongodb:
     *     mappings:
     *         App1: ~
     *         App2: xml
     *         App3: { type: attribute }
     *         App4: { type: xml, dir: config/doctrine/mapping }
     *         App5:
     *             type: xml
     *             dir: [app-mappings1/, app-mappings2/]
     *             alias: AppAlias
     *         arbitrary_key:
     *             type: xml
     *             dir: %kernel.dir%/../src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents
     *             prefix: DoctrineExtensions\Documents\
     *             alias: DExt
     *
     * In the case of bundles everything is really optional (which leads to autodetection for this bundle) but
     * in the mappings key everything except alias is a required argument.
     */
    protected function loadDocumentManagerBundlesMappingInformation(array $documentManager, Definition $odmConfigDef, ContainerBuilder $container): void
    {
        // reset state of drivers and alias map. They are only used by this methods and children.
        $this->drivers  = [];
        $this->aliasMap = [];

        $this->loadMappingInformation($documentManager, $container);
        $this->registerMappingDrivers($documentManager, $container);

        if ($odmConfigDef->hasMethodCall('setDocumentNamespaces')) {
            // TODO: Can we make a method out of it on Definition? replaceMethodArguments() or something.
            $calls = $odmConfigDef->getMethodCalls();
            foreach ($calls as $call) {
                if ($call[0] !== 'setDocumentNamespaces') {
                    continue;
                }

                $this->aliasMap = array_merge($call[1][0], $this->aliasMap);
            }

            $method = $odmConfigDef->removeMethodCall('setDocumentNamespaces');
        }

        $odmConfigDef->addMethodCall('setDocumentNamespaces', [$this->aliasMap]);
    }

    protected function getObjectManagerElementName(string $name): string
    {
        return 'doctrine_mongodb.odm.' . $name;
    }

    protected function getMappingObjectDefaultName(): string
    {
        return 'Document';
    }

    protected function getMappingResourceConfigDirectory(?string $bundleDir = null): string
    {
        if ($bundleDir !== null && is_dir($bundleDir . '/config/doctrine')) {
            return 'config/doctrine';
        }

        return 'Resources/config/doctrine';
    }

    protected function getMappingResourceExtension(): string
    {
        return 'mongodb';
    }

    protected function getMetadataDriverClass(string $driverType): string
    {
        return match ($driverType) {
            'driver_chain' => MappingDriverChain::class,
            'attribute' => AttributeDriver::class,
            'xml' => XmlDriver::class,
            default => throw new InvalidArgumentException(sprintf('Metadata driver not supported: "%s"', $driverType))
        };
    }

    public function getAlias(): string
    {
        return 'doctrine_mongodb';
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace(): string
    {
        return 'http://symfony.com/schema/dic/doctrine/odm/mongodb';
    }

    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../../config/schema';
    }

    /**
     * Loads a cache driver.
     *
     * @throws InvalidArgumentException
     */
    protected function loadCacheDriver(string $cacheName, string $objectManagerName, array $cacheDriver, ContainerBuilder $container): string
    {
        if (isset($cacheDriver['namespace'])) {
            return parent::loadCacheDriver($cacheName, $objectManagerName, $cacheDriver, $container);
        }

        $cacheDriverServiceId = $this->getObjectManagerElementName($objectManagerName . '_' . $cacheName);

        switch ($cacheDriver['type']) {
            case 'service':
                $container->setAlias($cacheDriverServiceId, new Alias($cacheDriver['id'], false));

                return $cacheDriverServiceId;

            case 'memcached':
                $memcachedClass         = $cacheDriver['class'] ?? MemcachedAdapter::class;
                $memcachedInstanceClass = $cacheDriver['instance_class'] ?? 'Memcached';
                $memcachedHost          = $cacheDriver['host'] ?? 'localhost';
                $memcachedPort          = $cacheDriver['port'] ?? '11211';
                $memcachedInstance      = new Definition($memcachedInstanceClass);
                $memcachedInstance->addMethodCall('addServer', [
                    $memcachedHost,
                    $memcachedPort,
                ]);
                $container->setDefinition($this->getObjectManagerElementName(sprintf('%s_memcached_instance', $objectManagerName)), $memcachedInstance);

                $cacheDef = new Definition($memcachedClass, [new Reference($this->getObjectManagerElementName(sprintf('%s_memcached_instance', $objectManagerName)))]);

                break;

            case 'redis':
                $redisClass         = $cacheDriver['class'] ?? RedisAdapter::class;
                $redisInstanceClass = $cacheDriver['instance_class'] ?? 'Redis';
                $redisHost          = $cacheDriver['host'] ?? 'localhost';
                $redisPort          = $cacheDriver['port'] ?? '6379';
                $redisInstance      = new Definition($redisInstanceClass);
                $redisInstance->addMethodCall('connect', [
                    $redisHost,
                    $redisPort,
                ]);
                $container->setDefinition($this->getObjectManagerElementName(sprintf('%s_redis_instance', $objectManagerName)), $redisInstance);

                $cacheDef = new Definition($redisClass, [new Reference($this->getObjectManagerElementName(sprintf('%s_redis_instance', $objectManagerName)))]);

                break;

            case 'apcu':
                $cacheDef = new Definition(ApcuAdapter::class);

                break;

            case 'array':
                $cacheDef = new Definition(ArrayAdapter::class);

                break;

            default:
                throw new InvalidArgumentException(sprintf('"%s" is an unrecognized cache driver.', $cacheDriver['type']));
        }

        $cacheDef->setPublic(false);
        $container->setDefinition($cacheDriverServiceId, $cacheDef);

        return $cacheDriverServiceId;
    }

    private static function getODMVersion(): string
    {
        if (self::$odmVersion === null) {
            try {
                self::$odmVersion = InstalledVersions::getPrettyVersion('doctrine/mongodb-odm') ?? 'no version';
            } catch (Throwable) {
                return 'unknown';
            }
        }

        return self::$odmVersion;
    }
}
