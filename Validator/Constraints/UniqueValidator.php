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

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Unique document validator checks if one field contains a unique value.
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jeremy Mikola <jmikola@gmail.com>
 */
class UniqueValidator extends ConstraintValidator
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object     $document
     * @param Constraint $constraint
     * @return Boolean
     * @throws InvalidArgumentException if the document is an embedded document
     */
    public function isValid($document, Constraint $constraint)
    {
        $dm = $this->registry->getManager($constraint->documentManager);

        $className = $this->context->getCurrentClass();
        $metadata = $dm->getClassMetadata($className);

        if ($metadata->isEmbeddedDocument) {
            throw new ConstraintDefinitionException('Unique validation of embedded documents is not supported');
        }

        $criteria = $this->createQueryArray($metadata, $document, $constraint->path);
        $results = $dm->getRepository($className)->findBy($criteria);
        $numResults = $results->count();

        // No documents match the query criteria. The criteria is unique.
        if (0 == $numResults) {
            return true;
        }

        /* Note: Doctrine\ODM\MongoDB\Cursor only hydrates through calls to
         * current(), so getNext() cannot be used here.
         */
        $results->next();
        $firstResult = $results->current();

        /* One document matched the query criteria and it is either the same as
         * the document being validated, or both documents share the same
         * identifier. The criteria is unique.
         */
        if (1 == $numResults && ($document === $firstResult || $metadata->getIdentifierValue($document) === $metadata->getIdentifierValue($firstResult))) {
            return true;
        }

        /* One or more documents matched the query criteria and they were not
         * the same as the document being validated. The criteria is not unique.
         */
        $invalidValue = $this->getFieldValueForPropertyPath($metadata, $document, $constraint->path);

        $path = $this->context->getPropertyPath();
        $this->context->addViolationAtPath(empty($path) ? $constraint->path : $path.'.'.$constraint->path, $constraint->message);

        // Be consistent with unique entity validator and return true after adding violation
        return true;
    }

    /**
     * Creates query criteria for the validator.
     *
     * @param ClassMetadata $metadata
     * @param object        $document
     * @param string        $path
     * @return array
     * @throws ConstraintDefinitionException if the field is not mapped or its type is unsupported
     */
    protected function createQueryArray(ClassMetadata $metadata, $document, $path)
    {
        $fieldMapping = $this->getFieldMappingForPropertyPath($metadata, $document, $path);

        if (!empty($fieldMapping['reference'])) {
            throw new ConstraintDefinitionException('Unique validation of document references is not supported');
        }

        switch ($fieldMapping['type']) {
            case 'one':
            case 'many':
                // TODO: implement support for validating embedded documents
                throw new ConstraintDefinitionException('Unique validation of embedded documents is not supported');
            case 'hash':
                return array($path => $this->getFieldValueForPropertyPath($metadata, $document, $path));
            case 'collection':
                return array($fieldMapping['fieldName'] => array('$in' => $metadata->getFieldValue($document, $fieldMapping['fieldName'])));
            default:
                return array($fieldMapping['fieldName'] => $metadata->getFieldValue($document, $fieldMapping['fieldName']));
        }
    }

    /**
     * Return the value of the field being checked for uniqueness.
     *
     * @param ClassMetadata $metadata
     * @param object        $document
     * @param string        $path
     * @return mixed
     * @throw ConstraintDefinitionException if no field mapping exists for the property path
     */
    private function getFieldValueForPropertyPath(ClassMetadata $metadata, $document, $path)
    {
        $fieldMapping = $this->getFieldMappingForPropertyPath($metadata, $document, $path);
        $fieldValue = $metadata->getFieldValue($document, $fieldMapping['fieldName']);

        /* For hash fields, traverse into the field value starting from the
         * second part of the property path. Null will be returned if traversal
         * cannot continue.
         */
        if ('hash' == $fieldMapping['type']) {
            $parts = explode('.', $path);
            array_shift($parts);

            foreach ($parts as $part) {
                $fieldValue = isset($fieldValue[$part]) ? $fieldValue[$part] : null;
            }
        }

        return $fieldValue;
    }

    /**
     * Return the document field mapping for a property path.
     *
     * @param ClassMetadata $metadata
     * @param object        $document
     * @param string        $path
     * @return array
     * @throw ConstraintDefinitionException if no field mapping exists for the property path
     */
    private function getFieldMappingForPropertyPath(ClassMetadata $metadata, $document, $path)
    {
        // Extract the first part of the property path before any dot separator
        $fieldName = false !== ($beforeDot = strstr($path, '.', true)) ? $beforeDot : $path;

        if (!$metadata->hasField($fieldName)) {
            throw new ConstraintDefinitionException(sprintf('Mapping for "%s" does not exist for "%s"', $path, $metadata->name));
        }

        return $metadata->getFieldMapping($fieldName);
    }
}
