<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver as BaseYamlDriver;

/**
 * YamlDriver that additionally looks for mapping information in a global file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kris Wallsmith <kris@symfony.com>
 */
class YamlDriver extends BaseYamlDriver
{
    protected $classCache;
    protected $globalFile = 'mapping';
    protected $fileExtension = '.mongodb.yml';

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }

    public function getAllClassNames()
    {
        if (null === $this->classCache) {
            $this->initialize();
        }

        return array_merge(parent::getAllClassNames(), array_keys($this->classCache));
    }

    public function getElement($className)
    {
        if (null === $this->classCache) {
            $this->initialize();
        }

        if (!isset($this->classCache[$className])) {
            $this->classCache[$className] = parent::getElement($className);
        }

        return $this->classCache[$className];
    }

    protected function initialize()
    {
        $this->classCache = array();
        foreach ($this->paths as $path) {
            if (file_exists($file = $path.'/'.$this->globalFile.$this->fileExtension)) {
                $this->classCache = array_merge($this->classCache, $this->loadMappingFile($file));
            }
        }
    }
}
