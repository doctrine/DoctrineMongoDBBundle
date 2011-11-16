<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Generator;

use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Generates a form class based on a Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineODMFormGenerator extends Generator {

    private $filesystem;
    private $skeletonDir;
    private $className;
    private $classPath;

    public function __construct(Filesystem $filesystem, $skeletonDir) {
        $this->filesystem = $filesystem;
        $this->skeletonDir = $skeletonDir;
    }

    public function getClassName() {
        return $this->className;
    }

    public function getClassPath() {
        return $this->classPath;
    }

    /**
     * Generates the entity form class if it does not exist.
     *
     * @param BundleInterface $bundle The bundle in which to create the class
     * @param ClassMetadataInfo $metadata The entity metadata class
     */
    public function generate(BundleInterface $bundle, ClassMetadataInfo $metadata) {
        $docNamespace = 'Document\\';
        $entity = substr($metadata->name, stripos($metadata->name, $docNamespace) + strlen($docNamespace));
        $parts = explode('\\', $entity);
        $entityClass = array_pop($parts);

        $this->className = $entityClass . 'Type';
        $dirPath = $bundle->getPath() . '/Form/Type';
        $this->classPath = $dirPath . '/' . str_replace('\\', '/', $entity) . 'Type.php';

        if (file_exists($this->classPath)) {
            throw new \RuntimeException(sprintf('Unable to generate the %s form class as it already exists under the %s file', $this->className, $this->classPath));
        }

        if (count($metadata->identifier) > 1) {
            throw new \RuntimeException('The form generator does not support entity classes with multiple primary keys.');
        }

        $parts = explode('\\', $entity);
        array_pop($parts);

        $this->renderFile($this->skeletonDir, 'FormType.php', $this->classPath, array(
            'dir' => $this->skeletonDir,
            'fields' => $this->getFieldsFromMetadata($metadata),
            'namespace' => $bundle->getNamespace(),
            'entity_namespace' => implode('\\', $parts),
            'form_class' => $this->className,
            'form_type_name' => strtolower(str_replace('\\', '_', $bundle->getNamespace()) . ($parts ? '_' : '') . implode('_', $parts) . '_' . $this->className),
            'data_class' => $metadata->name
        ));
    }

    /**
     * Returns an array of fields. Fields can be both column fields and
     * association fields.
     *
     * @param ClassMetadataInfo $metadata
     * @return array $fields
     */
    private function getFieldsFromMetadata(ClassMetadataInfo $metadata) {
        $fields = array();
        foreach ($metadata->fieldMappings as $field) {
            // Remove the primary key field
            if (!isset($field['id'])) {
                $fields[] = $field['fieldName'];
            }
        }

        return $fields;
    }
}
