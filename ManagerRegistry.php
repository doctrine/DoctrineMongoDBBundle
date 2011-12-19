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

class ManagerRegistry extends BaseManagerRegistry
{
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
