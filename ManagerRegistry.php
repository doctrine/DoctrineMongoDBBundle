<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bridge\Doctrine\ManagerRegistry as BaseManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function array_keys;
use function class_uses;

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
        $parentTraits = class_uses(parent::class);
        if (isset($parentTraits[ContainerAwareTrait::class])) {
            // this case should be removed when Symfony 3.4 becomes the lowest supported version
            // and then also, the constructor should type-hint Psr\Container\ContainerInterface
            $this->setContainer($container);
        } else {
            $this->container = $container;
        }

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
            try {
                return $this->getManager($name)->getConfiguration()->getDocumentNamespace($alias);
            } catch (MongoDBException $e) {
            }
        }

        throw MongoDBException::unknownDocumentNamespace($alias);
    }
}
