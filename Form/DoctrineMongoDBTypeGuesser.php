<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Form;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Tries to guess form types according to ODM mappings
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DoctrineMongoDBTypeGuesser implements FormTypeGuesserInterface
{
    /**
     * The Doctrine MongoDB document manager
     * @var DocumentManager
     */
    protected $documentManager = null;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @inheritDoc
     */
    public function guessType($class, $property)
    {
        if ($this->isMappedClass($class)) {
            $metadata = $this->documentManager->getClassMetadata($class);

            if ($metadata->hasAssociation($property)) {
                $multiple = $metadata->isCollectionValuedAssociation($property);
                $mapping = $metadata->getFieldMapping($property);

                return new TypeGuess(
                    'document',
                    array(
                        'document_manager' => $this->documentManager,
                        'class' => $mapping['targetDocument'],
                        'multiple' => $multiple,
                        'expanded' => $multiple
                    ),
                    Guess::HIGH_CONFIDENCE
                );
            } else {
                $fieldMapping = $metadata->getFieldMapping($property);
                switch ($fieldMapping['type'])
                {
                    case 'collection':
                        return new TypeGuess(
                            'Collection',
                            array(),
                            Guess::MEDIUM_CONFIDENCE
                        );
                    case 'boolean':
                        return new TypeGuess(
                            'checkbox',
                            array(),
                            Guess::HIGH_CONFIDENCE
                        );
                    case 'date':
                    case 'timestamp':
                        return new TypeGuess(
                            'datetime',
                            array(),
                           Guess::HIGH_CONFIDENCE
                        );
                    case 'float':
                        return new TypeGuess(
                            'number',
                            array(),
                            Guess::MEDIUM_CONFIDENCE
                        );
                    case 'int':
                        return new TypeGuess(
                            'integer',
                            array(),
                            Guess::MEDIUM_CONFIDENCE
                        );
                    case 'string':
                        return new TypeGuess(
                            'text',
                            array(),
                            Guess::MEDIUM_CONFIDENCE
                        );
                }
            }
        }

        return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
    }

    /**
     * @inheritDoc
     */
    public function guessRequired($class, $property)
    {
        if ($this->isMappedClass($class)) {
            $metadata = $this->documentManager->getClassMetadata($class);

            if ($metadata->hasField($property)) {
                if (!$metadata->isNullable($property)) {
                    return new ValueGuess(
                        true,
                        Guess::HIGH_CONFIDENCE
                    );
                }

                return new ValueGuess(
                    false,
                    Guess::MEDIUM_CONFIDENCE
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function guessMaxLength($class, $property)
    {
        if ($this->isMappedClass($class)) {
            $metadata = $this->documentManager->getClassMetadata($class);

            if (!$metadata->hasAssociation($property)) {
                $mapping = $metadata->getFieldMapping($property);


                if (isset($mapping['length'])) {
                    return new ValueGuess(
                        $mapping['length'],
                        Guess::HIGH_CONFIDENCE
                    );
                }
            }
        }
    }

    public function guessMinLength($class, $property)
    {
    }

    /**
     * Returns whether Doctrine 2 metadata exists for that class
     *
     * @param string $class
     * @return Boolean
     */
    protected function isMappedClass($class)
    {
        return !$this->documentManager->getConfiguration()->getMetadataDriverImpl()->isTransient($class);
    }
}
