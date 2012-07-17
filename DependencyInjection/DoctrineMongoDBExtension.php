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
        $loader->load('mongodb.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (empty($config['default_connection'])) {
            $keys = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }
        $container->setParameter('doctrine.odm.mongodb.default_connection', $config['default_connection']);

        if (empty($config['default_document_manager'])) {
            $keys = array_keys($config['document_managers']);
            $config['default_document_manager'] = reset($keys);
        }
        $container->setParameter('doctrine.odm.mongodb.default_document_manager', $config['default_document_manager']);

        // set some options as parameters and unset them
        $config = $this->overrideParameters($config, $container);

        // load the connections
        $this->loadConnections($config['connections'], $container);

        // load the document managers
        $this->loadDocumentManagers(
            $config['document_managers'],
            $config['default_document_manager'],
            $config['default_database'],
            $container
        );
    }

    /**
     * Uses some of the extension options to override DI extension parameters.
     *
     * @param array $options The available configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function overrideParameters($options, ContainerBuilder $container)
    {
        $overrides = array(
            'proxy_namespace',
            'proxy_dir',
            'auto_generate_proxy_classes',
            'hydrator_namespace',
            'hydrator_dir',
            'auto_generate_hydrator_classes',
            'default_commit_options',
        );

        foreach ($overrides as $key) {
            if (isset($options[$key])) {
                $container->setParameter('doctrine.odm.mongodb.'.$key, $options[$key]);

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
        $dms = array();
        foreach ($dmConfigs as $name => $documentManager) {
            $documentManager['name'] = $name;
            $this->loadDocumentManager(
                $documentManager,
                $defaultDM,
                $defaultDB,
                $container
            );
            $dms[$name] = sprintf('doctrine.odm.mongodb.%s_document_manager', $name);
        }
        $container->setParameter('doctrine.odm.mongodb.document_managers', $dms);
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
        $configServiceName = sprintf('doctrine.odm.mongodb.%s_configuration', $documentManager['name']);
        $connectionName = isset($documentManager['connection']) ? $documentManager['connection'] : $documentManager['name'];
        $defaultDatabase = isset($documentManager['database']) ? $documentManager['database'] : $defaultDB;

        if ($container->hasDefinition($configServiceName)) {
            $odmConfigDef = $container->getDefinition($configServiceName);
        } else {
            $odmConfigDef = new Definition('%doctrine.odm.mongodb.configuration.class%');
            $container->setDefinition($configServiceName, $odmConfigDef);
        }

        $this->loadDocumentManagerBundlesMappingInformation($documentManager, $odmConfigDef, $container);
        $this->loadObjectManagerCacheDriver($documentManager, $container, 'metadata_cache');

        $methods = array(
            'setMetadataCacheImpl' => new Reference(sprintf('doctrine.odm.mongodb.%s_metadata_cache', $documentManager['name'])),
            'setMetadataDriverImpl' => new Reference(sprintf('doctrine.odm.mongodb.%s_metadata_driver', $documentManager['name'])),
            'setProxyDir' => '%doctrine.odm.mongodb.proxy_dir%',
            'setProxyNamespace' => '%doctrine.odm.mongodb.proxy_namespace%',
            'setAutoGenerateProxyClasses' => '%doctrine.odm.mongodb.auto_generate_proxy_classes%',
            'setHydratorDir' => '%doctrine.odm.mongodb.hydrator_dir%',
            'setHydratorNamespace' => '%doctrine.odm.mongodb.hydrator_namespace%',
            'setAutoGenerateHydratorClasses' => '%doctrine.odm.mongodb.auto_generate_hydrator_classes%',
            'setDefaultDB' => $defaultDatabase,
            'setDefaultCommitOptions' => '%doctrine.odm.mongodb.default_commit_options%',
            'setRetryConnect' => $documentManager['retry_connect'],
            'setRetryQuery' => $documentManager['retry_query']
        );

        // logging
        $loggers = array();
        if ($container->getParameterBag()->resolveValue($documentManager['logging'])) {
            $loggers[] = new Reference('doctrine.odm.mongodb.logger');
        }

        // profiler
        if ($container->getParameterBag()->resolveValue($documentManager['profiler']['enabled'])) {
            $dataCollectorId = sprintf('doctrine.odm.mongodb.data_collector.%s', $container->getParameterBag()->resolveValue($documentManager['profiler']['pretty']) ? 'pretty' : 'standard');
            $loggers[] = new Reference($dataCollectorId);
            $container
                ->getDefinition($dataCollectorId)
                ->addTag('data_collector', array( 'id' => 'mongodb', 'template' => 'DoctrineMongoDBBundle:Collector:mongodb'))
            ;
        }

        if (1 < count($loggers)) {
            $methods['setLoggerCallable'] = array(new Reference('doctrine.odm.mongodb.logger.aggregate'), 'logQuery');
            $container
                ->getDefinition('doctrine.odm.mongodb.logger.aggregate')
                ->addArgument($loggers)
            ;
        } elseif ($loggers) {
            $methods['setLoggerCallable'] = array($loggers[0], 'logQuery');
        }

        foreach ($methods as $method => $arg) {
            if ($odmConfigDef->hasMethodCall($method)) {
                $odmConfigDef->removeMethodCall($method);
            }
            $odmConfigDef->addMethodCall($method, array($arg));
        }

        $odmDmArgs = array(
            new Reference(sprintf('doctrine.odm.mongodb.%s_connection', $connectionName)),
            new Reference(sprintf('doctrine.odm.mongodb.%s_configuration', $documentManager['name'])),
            // Document managers will share their connection's event manager
            new Reference(sprintf('doctrine.odm.mongodb.%s_connection.event_manager', $connectionName)),
        );
        $odmDmDef = new Definition('%doctrine.odm.mongodb.document_manager.class%', $odmDmArgs);
        $odmDmDef->setFactoryClass('%doctrine.odm.mongodb.document_manager.class%');
        $odmDmDef->setFactoryMethod('create');
        $odmDmDef->addTag('doctrine.odm.mongodb.document_manager');
        $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_document_manager', $documentManager['name']), $odmDmDef);

        if ($documentManager['name'] == $defaultDM) {
            $container->setAlias(
                'doctrine.odm.mongodb.document_manager',
                new Alias(sprintf('doctrine.odm.mongodb.%s_document_manager', $documentManager['name']))
            );
            $container->setAlias(
                'doctrine.odm.mongodb.event_manager',
                new Alias(sprintf('doctrine.odm.mongodb.%s_connection.event_manager', $connectionName))
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
        $cons = array();
        foreach ($connections as $name => $connection) {
            // Define an event manager for this connection
            $eventManagerId = sprintf('doctrine.odm.mongodb.%s_connection.event_manager', $name);
            $container->setDefinition($eventManagerId, new DefinitionDecorator('doctrine.odm.mongodb.connection.event_manager'));

            $odmConnArgs = array(
                isset($connection['server']) ? $connection['server'] : null,
                isset($connection['options']) ? $connection['options'] : array(),
                new Reference(sprintf('doctrine.odm.mongodb.%s_configuration', $name)),
                new Reference($eventManagerId),
            );
            $odmConnDef = new Definition('%doctrine.odm.mongodb.connection.class%', $odmConnArgs);
            $id = sprintf('doctrine.odm.mongodb.%s_connection', $name);
            $container->setDefinition($id, $odmConnDef);
            $cons[$name] = $id;
        }
        $container->setParameter('doctrine.odm.mongodb.connections', $cons);
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
     *  doctrine.orm:
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
        $this->drivers = array();
        $this->aliasMap = array();

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
        $odmConfigDef->addMethodCall('setDocumentNamespaces', array($this->aliasMap));
    }

    protected function getObjectManagerElementName($name)
    {
        return 'doctrine.odm.mongodb.' . $name;
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
