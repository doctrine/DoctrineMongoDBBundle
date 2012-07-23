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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * FrameworkExtension configuration structure.
 *
 * @author Ryan Weaver <ryan@thatsquality.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('doctrine_mongodb');

        $this->addDocumentManagersSection($rootNode);
        $this->addConnectionsSection($rootNode);

        $rootNode
            ->children()
                ->scalarNode('proxy_namespace')->defaultValue('MongoDBODMProxies')->end()
                ->scalarNode('proxy_dir')->defaultValue('%kernel.cache_dir%/doctrine/odm/mongodb/Proxies')->end()
                ->scalarNode('auto_generate_proxy_classes')->defaultValue(false)->end()
                ->scalarNode('hydrator_namespace')->defaultValue('Hydrators')->end()
                ->scalarNode('hydrator_dir')->defaultValue('%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators')->end()
                ->scalarNode('auto_generate_hydrator_classes')->defaultValue(false)->end()
                ->scalarNode('default_document_manager')->end()
                ->scalarNode('default_connection')->end()
                ->scalarNode('default_database')->defaultValue('default')->end()
                ->arrayNode('default_commit_options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('safe')
                            ->defaultTrue()
                            ->beforeNormalization()
                                ->ifTrue(function($v) { return is_numeric($v); })
                                ->then(function($v) { return (int) $v; })
                            ->end()
                            ->validate()
                                ->ifTrue(function($v) { return !(is_bool($v) || (is_int($v) && $v > 0)); })
                                ->thenInvalid('Boolean or positive integer expected for "safe" commit option')
                            ->end()
                        ->end()
                        ->booleanNode('fsync')->defaultFalse()->end()
                        ->scalarNode('timeout')
                            ->defaultValue(\MongoCursor::$timeout)
                            ->beforeNormalization()
                                ->ifTrue(function($v) { return is_numeric($v); })
                                ->then(function($v) { return (int) $v; })
                            ->end()
                            ->validate()
                                ->ifTrue(function($v) { return !is_int($v) || $v < -1; })
                                ->thenInvalid('Integer (-1 or greater) expected for "timeout" commit option')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Configures the "document_managers" section
     */
    private function addDocumentManagersSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('document_manager')
            ->children()
                ->arrayNode('document_managers')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->treatNullLike(array())
                        ->children()
                            ->scalarNode('connection')->end()
                            ->scalarNode('database')->end()
                            ->booleanNode('logging')->defaultValue('%kernel.debug%')->end()
                            ->arrayNode('profiler')
                                ->addDefaultsIfNotSet()
                                ->treatTrueLike(array('enabled' => true))
                                ->treatFalseLike(array('enabled' => false))
                                ->children()
                                    ->booleanNode('enabled')->defaultValue('%kernel.debug%')->end()
                                    ->booleanNode('pretty')->defaultValue('%kernel.debug%')->end()
                                ->end()
                            ->end()
                            ->scalarNode('auto_mapping')->defaultFalse()->end()
                            ->scalarNode('retry_connect')->defaultValue(0)->end()
                            ->scalarNode('retry_query')->defaultValue(0)->end()
                            ->arrayNode('metadata_cache_driver')
                                ->addDefaultsIfNotSet()
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return !is_array($v); })
                                    ->then(function($v) { return array('type' => $v); })
                                ->end()
                                ->children()
                                    ->scalarNode('type')->defaultValue('array')->end()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('host')->end()
                                    ->scalarNode('port')->end()
                                    ->scalarNode('instance_class')->end()
                                    ->scalarNode('id')->end()
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
                                        ->then(function($v) { return array ('type' => $v); })
                                    ->end()
                                    ->treatNullLike(array())
                                    ->treatFalseLike(array('mapping' => false))
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
            ->end()
        ;
    }

    /**
     * Adds the configuration for the "connections" key
     */
    private function addConnectionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('server')->defaultNull()->end()
                            ->arrayNode('options')
                                ->performNoDeepMerging()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('connect')->end()
                                    ->scalarNode('persist')->end()
                                    ->scalarNode('timeout')->end()
                                    ->scalarNode('replicaSet')->end()
                                    ->booleanNode('slaveOkay')->end()
                                    ->scalarNode('username')->end()
                                    ->scalarNode('password')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
