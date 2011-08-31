<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Unique document validator checks if one field contains a unique value.
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class UniqueValidator extends ConstraintValidator
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param object     $document
     * @param Constraint $constraint
     * @return Boolean
     * @throws InvalidArgumentException if the document is an embedded document
     */
    public function isValid($document, Constraint $constraint)
    {
        $dm = $this->getDocumentManager($constraint);

        $className = $this->context->getCurrentClass();
        $metadata = $dm->getClassMetadata($className);

        if ($metadata->isEmbeddedDocument) {
            throw new \InvalidArgumentException(sprintf("Document '%s' is an embedded document, and cannot be validated", $class));
        }

        $criteria = $this->getQueryArray($metadata, $document, $constraint->path);

        $repository = $dm->getRepository($className);
        $result = $dm->getRepository($className)->findBy($criteria);
        $numResult = count($result);

        /* If any results were found, the document's criteria is not unique
         * unless the single result is the document itself.
         */
        if (1 < $numResult || (1 == $numResult && $document !== $result->current())) {
            $oldPath = $this->context->getPropertyPath();
            $this->context->setPropertyPath(empty($oldPath) ? $constraint->path : $oldPath.'.'.$constraint->path);
            // TODO: specify invalidValue when adding violation
            $this->context->addViolation($constraint->message, array('{{ property }}' => $constraint->path), null);
            $this->context->setPropertyPath($oldPath);
        }

        return true;
    }

    protected function getQueryArray(ClassMetadata $metadata, $document, $path)
    {
        $class = $metadata->name;
        $field = $this->getFieldNameFromPropertyPath($path);
        if (!isset($metadata->fieldMappings[$field])) {
            throw new \LogicException('Mapping for \'' . $path . '\' doesn\'t exist for ' . $class);
        }
        $mapping = $metadata->fieldMappings[$field];
        if (isset($mapping['reference']) && $mapping['reference']) {
            throw new \LogicException('Cannot determine uniqueness of referenced document values');
        }
        switch ($mapping['type']) {
            case 'one':
                // TODO: implement support for embed one documents
            case 'many':
                // TODO: implement support for embed many documents
                throw new \RuntimeException('Not Implemented.');
            case 'hash':
                $value = $metadata->getFieldValue($document, $mapping['fieldName']);
                return array($path => $this->getFieldValueRecursively($path, $value));
            case 'collection':
                return array($mapping['fieldName'] => array('$in' => $metadata->getFieldValue($document, $mapping['fieldName'])));
            default:
                return array($mapping['fieldName'] => $metadata->getFieldValue($document, $mapping['fieldName']));
        }
    }

    /**
     * Returns the actual document field value
     *
     * E.g. document.someVal -> document
     *      user.emails      -> user
     *      username         -> username
     *
     * @param string $field
     * @return string
     */
    private function getFieldNameFromPropertyPath($field)
    {
        $pieces = explode('.', $field);
        return $pieces[0];
    }

    private function getFieldValueRecursively($fieldName, $value)
    {
        $pieces = explode('.', $fieldName);
        unset($pieces[0]);
        foreach ($pieces as $piece) {
            $value = $value[$piece];
        }
        return $value;
    }

    /**
     * Get the preferred document manager for the given Constraint.
     *
     * The default document manager will be returned by default if no document
     * manager name has been specified on the Constraint.
     *
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    private function getDocumentManager(Constraint $constraint)
    {
        $id = isset($constraint->documentManager)
            ? sprintf('doctrine.odm.mongodb.%s_document_manager', $constraint->documentManager)
            : 'doctrine.odm.mongodb.document_manager';

        return $this->container->get($id);
    }
}
