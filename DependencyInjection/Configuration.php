<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection;

use Doctrine\ODM\MongoDB\Configuration as ODMConfiguration;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use LogicException;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function class_parents;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;

/**
 * FrameworkExtension configuration structure.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('doctrine_mongodb');
        $rootNode    = $treeBuilder->getRootNode();

        $this->addDocumentManagersSection($rootNode);
        $this->addConnectionsSection($rootNode);
        $this->addResolveTargetDocumentsSection($rootNode);
        $this->addTypesSection($rootNode);

        $rootNode
            ->children()
                ->scalarNode('proxy_namespace')->defaultValue('MongoDBODMProxies')->end()
                ->scalarNode('proxy_dir')->defaultValue('%kernel.cache_dir%/doctrine/odm/mongodb/Proxies')->end()
                ->scalarNode('auto_generate_proxy_classes')
                    ->defaultValue(ODMConfiguration::AUTOGENERATE_EVAL)
                    ->beforeNormalization()
                    ->always(static function ($v) {
                        if ($v === false) {
                            return ODMConfiguration::AUTOGENERATE_EVAL;
                        }

                        if ($v === true) {
                            return ODMConfiguration::AUTOGENERATE_FILE_NOT_EXISTS;
                        }

                        return $v;
                    })
                    ->end()
                ->end()
                ->scalarNode('hydrator_namespace')->defaultValue('Hydrators')->end()
                ->scalarNode('hydrator_dir')->defaultValue('%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators')->end()
                ->scalarNode('auto_generate_hydrator_classes')
                    ->defaultValue(ODMConfiguration::AUTOGENERATE_NEVER)
                    ->beforeNormalization()
                    ->always(static function ($v) {
                        if ($v === false) {
                            return ODMConfiguration::AUTOGENERATE_NEVER;
                        }

                        if ($v === true) {
                            return ODMConfiguration::AUTOGENERATE_ALWAYS;
                        }

                        return $v;
                    })
                    ->end()
                ->end()
                ->scalarNode('persistent_collection_namespace')->defaultValue('PersistentCollections')->end()
                ->scalarNode('persistent_collection_dir')->defaultValue('%kernel.cache_dir%/doctrine/odm/mongodb/PersistentCollections')->end()
                ->scalarNode('auto_generate_persistent_collection_classes')->defaultValue(ODMConfiguration::AUTOGENERATE_NEVER)->end()
                ->scalarNode('fixture_loader')
                    ->defaultValue(ContainerAwareLoader::class)
                    ->beforeNormalization()
                        ->ifTrue(static function ($v) {
                            return ! ($v === ContainerAwareLoader::class || in_array(ContainerAwareLoader::class, class_parents($v)));
                        })
                        ->then(static function ($v) {
                            throw new LogicException(sprintf('The %s class is not a subclass of the ContainerAwareLoader', $v));
                        })
                    ->end()
                ->end()
                ->scalarNode('default_document_manager')->end()
                ->scalarNode('default_connection')->end()
                ->scalarNode('default_database')->defaultValue('default')->end()
                ->arrayNode('default_commit_options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('j')->end()
                        ->integerNode('timeout')->end()
                        ->scalarNode('w')->end()
                        ->integerNode('wtimeout')->end()
                        // Deprecated options
                        ->booleanNode('fsync')->info('Deprecated. Please use the "j" option instead.')->end()
                        ->scalarNode('safe')->info('Deprecated. Please use the "w" option instead.')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * Adds the "document_managers" config section.
     */
    private function addDocumentManagersSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('document_manager')
            ->children()
                ->arrayNode('document_managers')
                    ->useAttributeAsKey('id')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->treatNullLike([])
                        ->fixXmlConfig('filter')
                        ->children()
                            ->scalarNode('connection')->end()
                            ->scalarNode('database')->end()
                            ->booleanNode('logging')->defaultValue('%kernel.debug%')->end()
                            ->arrayNode('profiler')
                                ->addDefaultsIfNotSet()
                                ->treatTrueLike(['enabled' => true])
                                ->treatFalseLike(['enabled' => false])
                                ->children()
                                    ->booleanNode('enabled')->defaultValue('%kernel.debug%')->end()
                                    ->booleanNode('pretty')->defaultValue('%kernel.debug%')->end()
                                ->end()
                            ->end()
                            ->scalarNode('default_document_repository_class')->defaultValue(DocumentRepository::class)->end()
                            ->scalarNode('default_gridfs_repository_class')->defaultValue(DefaultGridFSRepository::class)->end()
                            ->scalarNode('repository_factory')->defaultValue('doctrine_mongodb.odm.container_repository_factory')->end()
                            ->scalarNode('persistent_collection_factory')->defaultNull()->end()
                            ->booleanNode('auto_mapping')->defaultFalse()->end()
                            ->arrayNode('filters')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->fixXmlConfig('parameter')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(static function ($v) {
                                            return ['class' => $v];
                                        })
                                    ->end()
                                    ->beforeNormalization()
                                        // The content of the XML node is returned as the "value" key so we need to rename it
                                        ->ifTrue(static function ($v) {
                                            return is_array($v) && isset($v['value']);
                                        })
                                        ->then(static function ($v) {
                                            $v['class'] = $v['value'];
                                            unset($v['value']);

                                            return $v;
                                        })
                                    ->end()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                        ->booleanNode('enabled')->defaultFalse()->end()
                                        ->arrayNode('parameters')
                                            ->treatNullLike([])
                                            ->useAttributeAsKey('name')
                                            ->prototype('variable')
                                                ->beforeNormalization()
                                                    // Detect JSON object and array syntax (for XML)
                                                    ->ifTrue(static function ($v) {
                                                        return is_string($v) && (preg_match('/\[.*\]/', $v) || preg_match('/\{.*\}/', $v));
                                                    })
                                                    // Decode objects to associative arrays for consistency with YAML
                                                    ->then(static function ($v) {
                                                        return json_decode($v, true);
                                                    })
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('metadata_cache_driver')
                                ->addDefaultsIfNotSet()
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(static function ($v) {
                                        return ['type' => $v];
                                    })
                                ->end()
                                ->children()
                                    ->scalarNode('type')->defaultValue('array')->end()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('host')->end()
                                    ->integerNode('port')->end()
                                    ->scalarNode('instance_class')->end()
                                    ->scalarNode('id')->end()
                                    ->scalarNode('namespace')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->fixXmlConfig('mapping')
                        ->children()
                            ->arrayNode('mappings')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(static function ($v) {
                                            return ['type' => $v];
                                        })
                                    ->end()
                                    ->treatNullLike([])
                                    ->treatFalseLike(['mapping' => false])
                                    ->performNoDeepMerging()
                                    ->children()
                                        ->scalarNode('mapping')->defaultValue(true)->end()
                                        ->scalarNode('type')->end()
                                        ->scalarNode('dir')->end()
                                        ->scalarNode('prefix')->end()
                                        ->scalarNode('alias')->end()
                                        ->booleanNode('is_bundle')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the "connections" config section.
     */
    private function addConnectionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('server')->end()
                            ->arrayNode('options')
                                ->performNoDeepMerging()
                                ->children()
                                    ->enumNode('authMechanism')
                                        ->values(['SCRAM-SHA-1', 'MONGODB-CR', 'MONGODB-X509', 'PLAIN', 'GSSAPI'])
                                    ->end()
                                    ->integerNode('connectTimeoutMS')->end()
                                    ->scalarNode('db')->end()
                                    ->scalarNode('authSource')
                                        ->validate()->ifNull()->thenUnset()->end()
                                    ->end()
                                    ->booleanNode('journal')->end()
                                    ->scalarNode('password')
                                        ->validate()->ifNull()->thenUnset()->end()
                                    ->end()
                                    ->enumNode('readPreference')
                                        ->values(['primary', 'primaryPreferred', 'secondary', 'secondaryPreferred', 'nearest'])
                                    ->end()
                                    ->arrayNode('readPreferenceTags')
                                        ->performNoDeepMerging()
                                        ->prototype('array')
                                            ->beforeNormalization()
                                                // Handle readPreferenceTag XML nodes
                                                ->ifTrue(static function ($v) {
                                                    return isset($v['readPreferenceTag']);
                                                })
                                                ->then(static function ($v) {
                                                    // Equivalent of fixXmlConfig() for inner node
                                                    if (isset($v['readPreferenceTag']['name'])) {
                                                        $v['readPreferenceTag'] = [$v['readPreferenceTag']];
                                                    }

                                                    return $v['readPreferenceTag'];
                                                })
                                            ->end()
                                            ->useAttributeAsKey('name')
                                            ->prototype('scalar')->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('replicaSet')
                                        ->validate()
                                            ->ifNull()->thenUnset()
                                        ->end()
                                        ->validate()
                                            ->ifTrue(static function ($v) {
                                                return ! is_string($v) && $v !== null;
                                            })->thenInvalid('The replicaSet option must be a string or null.')
                                        ->end()
                                    ->end()
                                    ->integerNode('socketTimeoutMS')->end()
                                    ->booleanNode('ssl')->end()
                                    ->scalarNode('username')
                                        ->validate()->ifNull()->thenUnset()->end()
                                    ->end()
                                    ->scalarNode('w')->end()
                                    ->integerNode('wTimeoutMS')->end()
                                    // Deprecated options
                                    ->booleanNode('fsync')->info('Deprecated. Please use the "journal" option instead.')->end()
                                    ->booleanNode('slaveOkay')->info('Deprecated. Please use the "readPreference" option instead.')->end()
                                    ->integerNode('timeout')->info('Deprecated. Please use the "connectTimeoutMS" option instead.')->end()
                                    ->integerNode('wTimeout')->info('Deprecated. Please use the "wTimeoutMS" option instead.')->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(static function ($v) {
                                        return count($v['readPreferenceTags']) === 0;
                                    })
                                    ->then(static function ($v) {
                                        unset($v['readPreferenceTags']);

                                        return $v;
                                    })
                                ->end()
                            ->end()
                            ->arrayNode('driver_options')
                                ->performNoDeepMerging()
                                ->children()
                                    ->scalarNode('context')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the "resolve_target_documents" config section.
     */
    private function addResolveTargetDocumentsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('resolve_target_document')
            ->children()
                ->arrayNode('resolve_target_documents')
                    ->useAttributeAsKey('interface')
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the "types" config section.
     */
    private function addTypesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('type')
            ->children()
                ->arrayNode('types')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static function ($v) {
                                return ['class' => $v];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
