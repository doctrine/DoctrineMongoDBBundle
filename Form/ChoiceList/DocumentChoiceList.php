<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList;

use Symfony\Component\Form\Util\PropertyPath;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\NoResultException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Common\Collections\Collection;

/**
 * Allows to choose from a list of documents
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentChoiceList extends ArrayChoiceList
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * The documents from which the user can choose
     *
     * This array is indexed by ID
     *
     * This property is initialized by initializeChoices(). It should only
     * be accessed through getDocument() and getDocuments().
     *
     * @var Collection
     */
    private $documents = array();

    /**
     * Contains the query builder that builds the query for fetching the
     * documents
     *
     * This property should only be accessed through queryBuilder.
     *
     * @var Builder
     */
    private $queryBuilder;

    /**
     * The field of which the identifier of the underlying class consists
     *
     * This property should only be accessed through identifier.
     *
     * @var string
     */
    private $identifier;

    /**
     * A cache for \ReflectionProperty instances for the underlying class
     *
     * This property should only be accessed through getReflProperty().
     *
     * @var array
     */
    private $reflProperties = array();

    /**
     * A cache for the UnitOfWork instance of Doctrine
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    private $propertyPath;

    public function __construct(DocumentManager $documentManager, $class, $property = null, $queryBuilder = null, $choices = array())
    {
        // If a query builder was passed, it must be a closure or Builder
        // instance
        if (!(null === $queryBuilder || $queryBuilder instanceof Builder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ODM\MongoDB\Query\Builder or \Closure');
        }

        if ($queryBuilder instanceof \Closure) {
            $queryBuilder = $queryBuilder($documentManager->getRepository($class));

            if (!$queryBuilder instanceof Builder) {
                throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ODM\MongoDB\Query\Builder');
            }
        }

        $this->documentManager = $documentManager;
        $this->class           = $class;
        $this->queryBuilder    = $queryBuilder;
        $this->unitOfWork      = $documentManager->getUnitOfWork();
        $this->identifier      = $documentManager->getClassMetadata($class)->getIdentifier();

        // The property option defines, which property (path) is used for
        // displaying documents as strings
        if ($property) {
            $this->propertyPath = new PropertyPath($property);
        }

        parent::__construct($choices);
    }

    /**
     * Initializes the choices and returns them
     *
     * The choices are generated from the documents.
     *
     * If the documents were passed in the "choices" option, this method
     * does not have any significant overhead. Otherwise, if a query builder
     * was passed in the "query_builder" option, this builder is now used
     * to construct a query which is executed. In the last case, all documents
     * for the underlying class are fetched from the repository.
     *
     * If the option "property" was passed, the property path in that option
     * is used as option values. Otherwise this method tries to convert
     * objects to strings using __toString().
     *
     * @return array  An array of choices
     */
    protected function load()
    {
        parent::load();

        if ($this->choices) {
            $documents = $this->choices;
        } else if ($queryBuilder = $this->queryBuilder) {
            $documents = $queryBuilder->getQuery()->execute();
        } else {
            $documents = $this->documentManager->getRepository($this->class)->findAll();
        }

        $this->choices = array();
        $this->documents = array();

        foreach ($documents as $key => $document) {
            if ($this->propertyPath) {
                // If the property option was given, use it
                $value = $this->propertyPath->getValue($document);
            } else {
                // Otherwise expect a __toString() method in the document
                $value = $document->__toString();
            }

            $id = $this->getIdentifierValue($document);
            $this->choices[$id] = $value;
            $this->documents[$id] = $document;
        }
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the according documents for the choices
     *
     * If the choices were not initialized, they are initialized now. This
     * is an expensive operation, except if the documents were passed in the
     * "choices" option.
     *
     * @return array  An array of documents
     */
    public function getDocuments()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->documents;
    }

    /**
     * Returns the document for the given key
     *
     * They are either fetched from the
     * internal document cache (if filled) or loaded from the database.
     *
     * @param  string $key  The choice key document ID
     * @return object       The matching document
     */
    public function getDocument($key)
    {
        if (!$this->loaded) {
            $this->load();
        }

        if ($this->documents) {
            return isset($this->documents[$key]) ? $this->documents[$key] : null;
        }
        return $this->documentManager->find($this->class, $key);
    }

    /**
     * Returns the \ReflectionProperty instance for a property of the
     * underlying class
     *
     * @param  string $property     The name of the property
     * @return \ReflectionProperty  The reflection instsance
     */
    private function getReflProperty($property)
    {
        if (!isset($this->reflProperties[$property])) {
            $this->reflProperties[$property] = new \ReflectionProperty($this->class, $property);
            $this->reflProperties[$property]->setAccessible(true);
        }

        return $this->reflProperties[$property];
    }

    /**
     * Returns the value of the identifier field of a document
     *
     * Doctrine must know about this document, that is, the document must already
     * be persisted or added to the identity map before. Otherwise an
     * exception is thrown.
     *
     * @param  object $document  The document for which to get the identifier
     * @throws FormException   If the document does not exist in Doctrine's
     *                         identity map
     */
    public function getIdentifierValue($document)
    {
        if (!$this->unitOfWork->isInIdentityMap($document)) {
            throw new FormException('documents passed to the choice field must be managed');
        }

        return $this->unitOfWork->getDocumentIdentifier($document);
    }
}
