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

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection;

use Symfony\Bridge\Doctrine\DependencyInjection\AbstractDoctrineExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Doctrine MongoDB ODM extension.
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineMongoDBExtension extends AbstractDoctrineExtension
{
    /**
     * Responds to the doctrine_mongodb configuration parameter.
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Load DoctrineMongoDBBundle/Resources/config/mongodb.xml
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader->load('mongodb.xml');

        if (empty($config['default_connection'])) {
            $keys = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }
        $container->setParameter('doctrine_mongodb.odm.default_connection', $config['default_connection']);

        if (empty($config['default_document_manager'])) {
            $keys = array_keys($config['document_managers']);
            $config['default_document_manager'] = reset($keys);
        }
        $container->setParameter('doctrine_mongodb.odm.default_document_manager', $config['default_document_manager']);

        // set some options as parameters and unset them
        $config = $this->overrideParameters($config, $container);

        // set the fixtures loader
        $container->setParameter('doctrine_mongodb.odm.fixture_loader', $config['fixture_loader']);

        // load the connections
        $this->loadConnections($config['connections'], $container);

        $config['document_managers'] = $this->fixManagersAutoMappings($config['document_managers'], $container->getParameter('kernel.bundles'));

        // load the document managers
        $this->loadDocumentManagers(
            $config['document_managers'],
            $config['default_document_manager'],
            $config['default_database'],
            $container
        );

        if ($config['resolve_target_documents']) {
            $def = $container->findDefinition('doctrine_mongodb.odm.listeners.resolve_target_document');
            foreach ($config['resolve_target_documents'] as $name => $implementation) {
                $def->addMethodCall('addResolveTargetDocument', [$name, $implementation, []]);
            }
            $def->addTag('doctrine_mongodb.odm.event_listener', ['event' => 'loadClassMetadata']);
        }

        // BC Aliases for Document Manager
        $container->setAlias('doctrine.odm.mongodb.document_manager', new Alias('doctrine_mongodb.odm.document_manager'));
    }

    /**
     * Uses some of the extension options to override DI extension parameters.
     *
     * @param array $options The available configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function overrideParameters($options, ContainerBuilder $container)
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
            if (isset($options[$key])) {
                $container->setParameter('doctrine_mongodb.odm.'.$key, $options[$key]);

                // the option should not be used, the parameter should be referenced
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * Loads the document managers configuration.
     *
     * @param array $dmConfigs An array of document manager configs
     * @param string $defaultDM The default document manager name
     * @param string $defaultDB The default db name
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManagers(array $dmConfigs, $defaultDM, $defaultDB, ContainerBuilder $container)
    {
        $dms = [];
        foreach ($dmConfigs as $name => $documentManager) {
            $documentManager['name'] = $name;
            $this->loadDocumentManager(
                $documentManager,
                $defaultDM,
                $defaultDB,
                $container
            );
            $dms[$name] = sprintf('doctrine_mongodb.odm.%s_document_manager', $name);
        }
        $container->setParameter('doctrine_mongodb.odm.document_managers', $dms);
    }

    /**
     * Loads a document manager configuration.
     *
     * @param array $documentManager        A document manager configuration array
     * @param string $defaultDM The default document manager name
     * @param string $defaultDB The default db name
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManager(array $documentManager, $defaultDM, $defaultDB, ContainerBuilder $container)
    {
        $configServiceName = sprintf('doctrine_mongodb.odm.%s_configuration', $documentManager['name']);
        $connectionName = isset($documentManager['connection']) ? $documentManager['connection'] : $documentManager['name'];
        $defaultDatabase = isset($documentManager['database']) ? $documentManager['database'] : $defaultDB;

        if ($container->hasDefinition($configServiceName)) {
            $odmConfigDef = $container->getDefinition($configServiceName);
        } else {
            $odmConfigDef = new Definition('%doctrine_mongodb.odm.configuration.class%');
            $container->setDefinition($configServiceName, $odmConfigDef);
        }

        $this->loadDocumentManagerBundlesMappingInformation($documentManager, $odmConfigDef, $container);
        $this->loadObjectManagerCacheDriver($documentManager, $container, 'metadata_cache');

        $methods = [
            'setMetadataCacheImpl' => new Reference(sprintf('doctrine_mongodb.odm.%s_metadata_cache', $documentManager['name'])),
            'setMetadataDriverImpl' => new Reference(sprintf('doctrine_mongodb.odm.%s_metadata_driver', $documentManager['name'])),
            'setProxyDir' => '%doctrine_mongodb.odm.proxy_dir%',
            'setProxyNamespace' => '%doctrine_mongodb.odm.proxy_namespace%',
            'setAutoGenerateProxyClasses' => '%doctrine_mongodb.odm.auto_generate_proxy_classes%',
            'setHydratorDir' => '%doctrine_mongodb.odm.hydrator_dir%',
            'setHydratorNamespace' => '%doctrine_mongodb.odm.hydrator_namespace%',
            'setAutoGenerateHydratorClasses' => '%doctrine_mongodb.odm.auto_generate_hydrator_classes%',
            'setDefaultDB' => $defaultDatabase,
            'setDefaultCommitOptions' => '%doctrine_mongodb.odm.default_commit_options%',
            'setRetryConnect' => $documentManager['retry_connect'],
            'setRetryQuery' => $documentManager['retry_query'],
            'setDefaultRepositoryClassName' => $documentManager['default_repository_class'],
            'setPersistentCollectionDir' => '%doctrine_mongodb.odm.persistent_collection_dir%',
            'setPersistentCollectionNamespace' => '%doctrine_mongodb.odm.persistent_collection_namespace%',
            'setAutoGeneratePersistentCollectionClasses' => '%doctrine_mongodb.odm.auto_generate_persistent_collection_classes%',
        ];

        if ($documentManager['repository_factory']) {
            $methods['setRepositoryFactory'] = new Reference($documentManager['repository_factory']);
        }

        if ($documentManager['persistent_collection_factory']) {
            $methods['setPersistentCollectionFactory'] = new Reference($documentManager['persistent_collection_factory']);
        }

        // logging
        $loggers = [];
        if ($container->getParameterBag()->resolveValue($documentManager['logging'])) {
            $loggers[] = new Reference('doctrine_mongodb.odm.logger');
        }

        // profiler
        if ($container->getParameterBag()->resolveValue($documentManager['profiler']['enabled'])) {
            $dataCollectorId = sprintf('doctrine_mongodb.odm.data_collector.%s', $container->getParameterBag()->resolveValue($documentManager['profiler']['pretty']) ? 'pretty' : 'standard');
            $loggers[] = new Reference($dataCollectorId);
            $container
                ->getDefinition($dataCollectorId)
                ->addTag('data_collector', ['id' => 'mongodb', 'template' => 'DoctrineMongoDBBundle:Collector:mongodb'])
            ;
        }

        if (1 < count($loggers)) {
            $methods['setLoggerCallable'] = [new Reference('doctrine_mongodb.odm.logger.aggregate'), 'logQuery'];
            $container
                ->getDefinition('doctrine_mongodb.odm.logger.aggregate')
                ->addArgument($loggers)
            ;
        } elseif ($loggers) {
            $methods['setLoggerCallable'] = [$loggers[0], 'logQuery'];
        }

        $enabledFilters = [];
        foreach ($documentManager['filters'] as $name => $filter) {
            $parameters = isset($filter['parameters']) ? $filter['parameters'] : [];
            $odmConfigDef->addMethodCall('addFilter', [$name, $filter['class'], $parameters]);
            if ($filter['enabled']) {
                $enabledFilters[] = $name;
            }
        }

        $managerConfiguratorName = sprintf('doctrine_mongodb.odm.%s_manager_configurator', $documentManager['name']);

        $managerConfiguratorDef = $container
            ->setDefinition($managerConfiguratorName, new DefinitionDecorator('doctrine_mongodb.odm.manager_configurator.abstract'))
            ->replaceArgument(0, $enabledFilters)
        ;


        foreach ($methods as $method => $arg) {
            if ($odmConfigDef->hasMethodCall($method)) {
                $odmConfigDef->removeMethodCall($method);
            }
            $odmConfigDef->addMethodCall($method, [$arg]);
        }

        $odmDmArgs = [
            new Reference(sprintf('doctrine_mongodb.odm.%s_connection', $connectionName)),
            new Reference(sprintf('doctrine_mongodb.odm.%s_configuration', $documentManager['name'])),
            // Document managers will share their connection's event manager
            new Reference(sprintf('doctrine_mongodb.odm.%s_connection.event_manager', $connectionName)),
        ];
        $odmDmDef = new Definition('%doctrine_mongodb.odm.document_manager.class%', $odmDmArgs);
        $odmDmDef->setFactory(['%doctrine_mongodb.odm.document_manager.class%', 'create']);
        $odmDmDef->addTag('doctrine_mongodb.odm.document_manager');

        $container
            ->setDefinition(sprintf('doctrine_mongodb.odm.%s_document_manager', $documentManager['name']), $odmDmDef)
            ->setConfigurator([new Reference($managerConfiguratorName), 'configure'])
        ;

        if ($documentManager['name'] == $defaultDM) {
            $container->setAlias(
                'doctrine_mongodb.odm.document_manager',
                new Alias(sprintf('doctrine_mongodb.odm.%s_document_manager', $documentManager['name']))
            );
            $container->setAlias(
                'doctrine_mongodb.odm.event_manager',
                new Alias(sprintf('doctrine_mongodb.odm.%s_connection.event_manager', $connectionName))
            );
        }
    }

    /**
     * Loads the configured connections.
     *
     * @param array $config An array of connections configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadConnections(array $connections, ContainerBuilder $container)
    {
        $cons = [];
        foreach ($connections as $name => $connection) {
            // Define an event manager for this connection
            $eventManagerId = sprintf('doctrine_mongodb.odm.%s_connection.event_manager', $name);
            $container->setDefinition($eventManagerId, new DefinitionDecorator('doctrine_mongodb.odm.connection.event_manager'));

            $odmConnArgs = [
                isset($connection['server']) ? $connection['server'] : null,
                isset($connection['options']) ? $connection['options'] : [],
                new Reference(sprintf('doctrine_mongodb.odm.%s_configuration', $name)),
                new Reference($eventManagerId),
                $this->normalizeDriverOptions($connection),
            ];
            $odmConnDef = new Definition('%doctrine_mongodb.odm.connection.class%', $odmConnArgs);
            $id = sprintf('doctrine_mongodb.odm.%s_connection', $name);
            $container->setDefinition($id, $odmConnDef);
            $cons[$name] = $id;
        }
        $container->setParameter('doctrine_mongodb.odm.connections', $cons);
    }

    /**
     * Normalizes the driver options array
     *
     * @param array $connection
     *
     * @return array|null
     */
    private function normalizeDriverOptions(array $connection)
    {
        if (! isset($connection['driver_options'])) {
            return [];
        }

        if (isset($connection['driver_options']['context'])) {
            $connection['driver_options']['context'] = new Reference($connection['driver_options']['context']);
        }

        return $connection['driver_options'];
    }

    /**
     * Loads an ODM document managers bundle mapping information.
     *
     * There are two distinct configuration possibilities for mapping information:
     *
     * 1. Specify a bundle and optionally details where the entity and mapping information reside.
     * 2. Specify an arbitrary mapping location.
     *
     * @example
     *
     *  doctrine_mongodb:
     *     mappings:
     *         MyBundle1: ~
     *         MyBundle2: yml
     *         MyBundle3: { type: annotation, dir: Documents/ }
     *         MyBundle4: { type: xml, dir: Resources/config/doctrine/mapping }
     *         MyBundle5:
     *             type: yml
     *             dir: [bundle-mappings1/, bundle-mappings2/]
     *             alias: BundleAlias
     *         arbitrary_key:
     *             type: xml
     *             dir: %kernel.dir%/../src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents
     *             prefix: DoctrineExtensions\Documents\
     *             alias: DExt
     *
     * In the case of bundles everything is really optional (which leads to autodetection for this bundle) but
     * in the mappings key everything except alias is a required argument.
     *
     * @param array $documentManager A configured ODM entity manager.
     * @param Definition A Definition instance
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManagerBundlesMappingInformation(array $documentManager, Definition $odmConfigDef, ContainerBuilder $container)
    {
        // reset state of drivers and alias map. They are only used by this methods and children.
        $this->drivers = [];
        $this->aliasMap = [];

        $this->loadMappingInformation($documentManager, $container);
        $this->registerMappingDrivers($documentManager, $container);

        if ($odmConfigDef->hasMethodCall('setDocumentNamespaces')) {
            // TODO: Can we make a method out of it on Definition? replaceMethodArguments() or something.
            $calls = $odmConfigDef->getMethodCalls();
            foreach ($calls as $call) {
                if ($call[0] == 'setDocumentNamespaces') {
                    $this->aliasMap = array_merge($call[1][0], $this->aliasMap);
                }
            }
            $method = $odmConfigDef->removeMethodCall('setDocumentNamespaces');
        }
        $odmConfigDef->addMethodCall('setDocumentNamespaces', [$this->aliasMap]);
    }

    protected function getObjectManagerElementName($name)
    {
        return 'doctrine_mongodb.odm.' . $name;
    }

    protected function getMappingObjectDefaultName()
    {
        return 'Document';
    }

    protected function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/doctrine';
    }

    protected function getMappingResourceExtension()
    {
        return 'mongodb';
    }

    public function getAlias()
    {
        return 'doctrine_mongodb';
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/doctrine/odm/mongodb';
    }

    /**
     * @return string
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }
}
