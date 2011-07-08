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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * References all Doctrine connections and document managers in a given Container.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Registry implements RegistryInterface
{
    private $container;
    private $connections;
    private $documentManagers;
    private $defaultConnection;
    private $defaultDocumentManager;

    public function __construct(ContainerInterface $container, array $connections, array $documentManagers, $defaultConnection, $defaultDocumentManager)
    {
        $this->container = $container;
        $this->connections = $connections;
        $this->documentManagers = $documentManagers;
        $this->defaultConnection = $defaultConnection;
        $this->defaultDocumentManager = $defaultDocumentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConnectionName()
    {
        return $this->defaultConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection($name = null)
    {
        if (null === $name) {
            $name = $this->defaultConnection;
        }

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('Doctrine MongoDB Connection named "%s" does not exist.', $name));
        }

        return $this->container->get($this->connections[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnections()
    {
        $connections = array();
        foreach ($this->connections as $name => $id) {
            $connections[$name] = $this->container->get($id);
        }

        return $connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionNames()
    {
        return $this->connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDocumentManagerName()
    {
        return $this->defaultDocumentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentManager($name = null)
    {
        if (null === $name) {
            $name = $this->defaultDocumentManager;
        }

        if (!isset($this->documentManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Doctrine DocumentManager named "%s" does not exist.', $name));
        }

        return $this->container->get($this->documentManagers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentManagers()
    {
        $dms = array();
        foreach ($this->documentManagers as $name => $id) {
            $dms[$name] = $this->container->get($id);
        }

        return $dms;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentNamespace($alias)
    {
        foreach (array_keys($this->documentManagers) as $name) {
            try {
                return $this->getDocumentManager($name)->getConfiguration()->getDocumentNamespace($alias);
            } catch (MongoDBException $e) {
            }
        }

        throw MongoDBException::unknownDocumentNamespace($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentManagerNames()
    {
        return $this->documentManagers;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($documentName, $documentManagerName = null)
    {
        return $this->getDocumentManager($documentManagerName)->getRepository($documentName);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentManagerForObject($object)
    {
        foreach ($this->documentManagers as $id) {
            $dm = $this->container->get($id);

            if ($dm->getConfiguration()->getMetadataDriverImpl()->isTransient($object)) {
                return $dm;
            }
        }
    }
}
