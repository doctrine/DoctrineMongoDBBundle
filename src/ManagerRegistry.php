<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry as BaseManagerRegistry;

use function array_keys;

class ManagerRegistry extends BaseManagerRegistry
{
    /**
     * @param string $name
     * @param string $defaultConnection
     * @param string $defaultManager
     * @param string $proxyInterfaceName
     */
    public function __construct($name, array $connections, array $managers, $defaultConnection, $defaultManager, $proxyInterfaceName, ?ContainerInterface $container = null)
    {
        $this->container = $container;

        parent::__construct($name, $connections, $managers, $defaultConnection, $defaultManager, $proxyInterfaceName);
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $alias
     *
     * @return string
     *
     * @throws MongoDBException
     */
    public function getAliasNamespace($alias)
    {
        foreach (array_keys($this->getManagers()) as $name) {
            $objectManager = $this->getManager($name);

            if (! $objectManager instanceof DocumentManager) {
                continue;
            }

            try {
                return $objectManager->getConfiguration()->getDocumentNamespace($alias);
            } catch (MongoDBException $e) {
            }
        }

        throw MongoDBException::unknownDocumentNamespace($alias);
    }
}
