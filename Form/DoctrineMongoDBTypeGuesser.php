<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\DoctrineMongoDBBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Tries to guess form types according to ODM mappings
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DoctrineMongoDBTypeGuesser implements FormTypeGuesserInterface
{
    protected $registry;

    private $cache = array();

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     */
    public function guessType($class, $property)
    {
        if (!$ret = $this->getMetadata($class)) {
            return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }

        list($metadata, $name) = $ret;

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping = $metadata->getFieldMapping($property);

            return new TypeGuess(
                'document',
                array(
                    'document_manager' => $name,
                    'class' => $mapping['targetDocument'],
                    'multiple' => $multiple,
                    'expanded' => $multiple
                ),
                Guess::HIGH_CONFIDENCE
            );
        }

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

    /**
     * @inheritDoc
     */
    public function guessRequired($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret[0]->hasField($property)) {
            if (!$ret[0]->isNullable($property)) {
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

    /**
     * @inheritDoc
     */
    public function guessMaxLength($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret[0]->hasField($property) && !$ret[0]->hasAssociation($property)) {
            $mapping = $ret[0]->getFieldMapping($property);

            if (isset($mapping['length'])) {
                return new ValueGuess(
                    $mapping['length'],
                    Guess::HIGH_CONFIDENCE
                );
            }
        }
    }

    public function guessMinLength($class, $property)
    {
    }

    protected function getMetadata($class)
    {
        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $this->cache[$class] = null;
        foreach ($this->registry->getManagers() as $name => $dm) {
            try {
                return $this->cache[$class] = array($dm->getClassMetadata($class), $name);
            } catch (MongoDBException $e) {
                // not an entity or mapped super class
            }
        }
    }
}
