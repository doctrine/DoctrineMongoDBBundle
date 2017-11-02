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

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bridge\Doctrine\ManagerRegistry as BaseManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ManagerRegistry extends BaseManagerRegistry
{
    public function __construct($name, array $connections, array $managers, $defaultConnection, $defaultManager, $proxyInterfaceName, ContainerInterface $container = null)
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
     * @return string
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
