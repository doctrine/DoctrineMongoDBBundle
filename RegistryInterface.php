<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * References Doctrine connections and document managers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface RegistryInterface
{
    /**
     * Gets the default connection name.
     *
     * @return string The default connection name
     */
    function getDefaultConnectionName();

    /**
     * Gets the named connection.
     *
     * @param string $name The connection name (null for the default one)
     *
     * @return Connection
     */
    function getConnection($name = null);

    /**
     * Gets an array of all registered connections
     *
     * @return array An array of Connection instances
     */
    function getConnections();

    /**
     * Gets all connection names.
     *
     * @return array An array of connection names
     */
    function getConnectionNames();

    /**
     * Gets the default document manager name.
     *
     * @return string The default document manager name
     */
    function getDefaultDocumentManagerName();

    /**
     * Gets a named document manager.
     *
     * @param string $name The document manager name (null for the default one)
     *
     * @return DocumentManager
     */
    function getDocumentManager($name = null);

    /**
     * Gets an array of all registered document managers
     *
     * @return array An array of DocumentManager instances
     */
    function getDocumentManagers();

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * This method looks for the alias in all registered document managers.
     *
     * @param string $alias The alias
     *
     * @return string The full namespace
     *
     * @see Configuration::getDocumentNamespace
     */
    function getDocumentNamespace($alias);

    /**
     * Gets all document manager names.
     *
     * @return array An array of document manager names
     */
    function getDocumentManagerNames();

    /**
     * Gets the DocumentRepository for a document.
     *
     * @param string $documentName        The name of the document.
     * @param string $documentManagerNAme The document manager name (null for the default one)
     *
     * @return Doctrine\ODM\MongoDB\DocumentRepository
     */
    function getRepository($documentName, $documentManagerName = null);

    /**
     * Gets the document manager associated with a given object.
     *
     * @param object $object A Doctrine Document
     *
     * @return DocumentManager|null
     */
    function getDocumentManagerForObject($object);
}
