<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterMappingsPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class for Symfony bundles to configure mappings for model classes not in the
 * automapped folder.
 *
 * NOTE: alias is only supported by Symfony 2.6+ and will be ignored with older versions.
 */
class DoctrineMongoDBMappingsPass extends RegisterMappingsPass
{
    /**
     * You should not directly instantiate this class but use one of the
     * factory methods.
     *
     * @param Definition|Reference $driver            the driver to use
     * @param array                $namespaces        list of namespaces this driver should handle
     * @param string[]             $managerParameters list of parameters that could tell the manager name to use
     * @param bool                 $enabledParameter  if specified, the compiler pass only
     *                                                executes if this parameter exists in the service container.
     * @param string[]             $aliasMap          Map of alias to namespace.
     */
    public function __construct($driver, array $namespaces, array $managerParameters, $enabledParameter = false, array $aliasMap = [])
    {
        $managerParameters[] = 'doctrine_mongodb.odm.default_document_manager';
        parent::__construct(
            $driver,
            $namespaces,
            $managerParameters,
            'doctrine_mongodb.odm.%s_metadata_driver',
            $enabledParameter,
            'doctrine_mongodb.odm.%s_configuration',
            'addDocumentNamespace',
            $aliasMap
        );
    }

    /**
     * @param array    $mappings          Hashmap of directory path to namespace
     * @param string[] $managerParameters List of parameters that could which object manager name
     *                                    your bundle uses. This compiler pass will automatically
     *                                    append the parameter name for the default entity manager
     *                                    to this list.
     * @param string   $enabledParameter  Service container parameter that must be present to
     *                                    enable the mapping. Set to false to not do any check,
     *                                    optional.
     * @param string[] $aliasMap          Map of alias to namespace.
     *
     * @return DoctrineMongoDBMappingsPass
     */
    public static function createXmlMappingDriver(array $mappings, array $managerParameters, $enabledParameter = false, array $aliasMap = [])
    {
        $arguments = [$mappings, '.mongodb.xml'];
        $locator   = new Definition(SymfonyFileLocator::class, $arguments);
        $driver    = new Definition(XmlDriver::class, [$locator]);

        return new DoctrineMongoDBMappingsPass($driver, $mappings, $managerParameters, $enabledParameter, $aliasMap);
    }

    /**
     * @param array    $mappings          Hashmap of directory path to namespace
     * @param string[] $managerParameters List of parameters that could which object manager name
     *                                    your bundle uses. This compiler pass will automatically
     *                                    append the parameter name for the default entity manager
     *                                    to this list.
     * @param string   $enabledParameter  Service container parameter that must be present to
     *                                    enable the mapping. Set to false to not do any check,
     *                                    optional.
     * @param string[] $aliasMap          Map of alias to namespace.
     *
     * @return DoctrineMongoDBMappingsPass
     */
    public static function createPhpMappingDriver(array $mappings, array $managerParameters = [], $enabledParameter = false, array $aliasMap = [])
    {
        $arguments = [$mappings, '.php'];
        $locator   = new Definition(SymfonyFileLocator::class, $arguments);
        $driver    = new Definition(PHPDriver::class, [$locator]);

        return new DoctrineMongoDBMappingsPass($driver, $mappings, $managerParameters, $enabledParameter, $aliasMap);
    }

    /**
     * @param array    $namespaces        List of namespaces that are handled with annotation mapping
     * @param array    $directories       List of directories to look for annotation mapping files
     * @param string[] $managerParameters List of parameters that could which object manager name
     *                                    your bundle uses. This compiler pass will automatically
     *                                    append the parameter name for the default entity manager
     *                                    to this list.
     * @param string   $enabledParameter  Service container parameter that must be present to
     *                                    enable the mapping. Set to false to not do any check,
     *                                    optional.
     * @param string[] $aliasMap          Map of alias to namespace.
     *
     * @return DoctrineMongoDBMappingsPass
     */
    public static function createAnnotationMappingDriver(array $namespaces, array $directories, array $managerParameters, $enabledParameter = false, array $aliasMap = [])
    {
        $driver = new Definition(AnnotationDriver::class, [new Reference('annotation_reader'), $directories]);

        return new DoctrineMongoDBMappingsPass($driver, $namespaces, $managerParameters, $enabledParameter, $aliasMap);
    }

    /**
     * @param array    $namespaces        List of namespaces that are handled with static php mapping
     * @param array    $directories       List of directories to look for static php mapping files
     * @param string[] $managerParameters List of parameters that could which object manager name
     *                                    your bundle uses. This compiler pass will automatically
     *                                    append the parameter name for the default entity manager
     *                                    to this list.
     * @param string   $enabledParameter  Service container parameter that must be present to
     *                                    enable the mapping. Set to false to not do any check,
     *                                    optional.
     * @param string[] $aliasMap          Map of alias to namespace.
     *
     * @return DoctrineMongoDBMappingsPass
     */
    public static function createStaticPhpMappingDriver(array $namespaces, array $directories, array $managerParameters = [], $enabledParameter = false, array $aliasMap = [])
    {
        $driver = new Definition(StaticPHPDriver::class, [$directories]);

        return new DoctrineMongoDBMappingsPass($driver, $namespaces, $managerParameters, $enabledParameter, $aliasMap);
    }
}
