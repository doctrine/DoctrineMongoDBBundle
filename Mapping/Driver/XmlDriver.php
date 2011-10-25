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

use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver as BaseXmlDriver;
use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * XmlDriver that additionally looks for mapping information in a global file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kris Wallsmith <kris@symfony.com>
 */
class XmlDriver extends BaseXmlDriver
{
    protected $prefixes = array();
    protected $globalBasename;
    protected $classCache;
    protected $fileExtension = '.mongodb.xml';

    public function __construct($prefixesByPath = array())
    {
        parent::__construct(array_keys($prefixesByPath));

        $this->setNamespacePrefixes($prefixesByPath);
    }

    public function setGlobalBasename($file)
    {
        $this->globalBasename = $file;
    }

    public function getGlobalBasename()
    {
        return $this->globalBasename;
    }

    public function setNamespacePrefixes($prefixes)
    {
        $this->prefixes = $prefixes;
    }

    public function getNamespacePrefixes()
    {
        return $this->prefixes;
    }

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }

    public function getAllClassNames()
    {
        if (null === $this->classCache) {
            $this->initialize();
        }

        $classes = array();

        if ($this->paths) {
            foreach ((array) $this->paths as $path) {
                if (!is_dir($path)) {
                    throw MongoDBException::fileMappingDriversRequireConfiguredDirectoryPath();
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    $fileName = $file->getBasename($this->fileExtension);

                    if ($fileName == $file->getBasename() || $fileName == $this->globalBasename) {
                        continue;
                    }

                    // NOTE: All files found here means classes are not transient!
                    if (isset($this->prefixes[$path])) {
                        $classes[] = $this->prefixes[$path].'\\'.str_replace('.', '\\', $fileName);
                    } else {
                        $classes[] = str_replace('.', '\\', $fileName);
                    }
                }
            }
        }

        return array_merge($classes, array_keys($this->classCache));
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
        if (null !== $this->globalBasename) {
            foreach ($this->paths as $path) {
                if (file_exists($file = $path.'/'.$this->globalBasename.$this->fileExtension)) {
                    $this->classCache = array_merge($this->classCache, $this->loadMappingFile($file));
                }
            }
        }
    }

    protected function findMappingFile($className)
    {
        $defaultFileName = str_replace('\\', '.', $className) . $this->fileExtension;
        foreach ($this->paths as $path) {
            if (!isset($this->prefixes[$path])) {
                if (file_exists($path . DIRECTORY_SEPARATOR . $defaultFileName)) {
                    return $path . DIRECTORY_SEPARATOR . $defaultFileName;
                }

                continue;
            }

            $prefix = $this->prefixes[$path];

            if (0 !== strpos($className, $prefix.'\\')) {
                continue;
            }

            $filename = $path.'/'.strtr(substr($className, strlen($prefix)+1), '\\', '.').$this->fileExtension;
            if (file_exists($filename)) {
                return $filename;
            }

            throw MongoDBException::mappingNotFound($className, $filename);
        }

        throw MongoDBException::mappingNotFound($className, substr($className, strrpos($className, '\\') + 1).$this->fileExtension);
    }
}
