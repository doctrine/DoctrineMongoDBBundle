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

namespace Doctrine\Bundle\MongoDBBundle\Form;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tries to guess form types according to ODM mappings
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DoctrineMongoDBTypeGuesser implements FormTypeGuesserInterface
{
    protected $registry;

    private $cache = [];

    private $typeFQCN;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->typeFQCN = method_exists(AbstractType::class, 'getBlockPrefix');
    }

    /**
     * @inheritDoc
     */
    public function guessType($class, $property)
    {
        if (!$ret = $this->getMetadata($class)) {
            return new TypeGuess($this->typeFQCN ? TextType::class : 'text', [], Guess::LOW_CONFIDENCE);
        }

        list($metadata, $name) = $ret;

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping = $metadata->getFieldMapping($property);

            return new TypeGuess(
                $this->typeFQCN ? DocumentType::class : 'document',
                [
                    'class' => $mapping['targetDocument'],
                    'multiple' => $multiple,
                    'expanded' => $multiple
                ],
                Guess::HIGH_CONFIDENCE
            );
        }

        $fieldMapping = $metadata->getFieldMapping($property);
        switch ($fieldMapping['type'])
        {
            case 'collection':
                return new TypeGuess(
                    $this->typeFQCN ? CollectionType::class : 'collection',
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'bool':
            case 'boolean':
                return new TypeGuess(
                    $this->typeFQCN ? CheckboxType::class : 'checkbox',
                    [],
                    Guess::HIGH_CONFIDENCE
                );
            case 'date':
            case 'timestamp':
                return new TypeGuess(
                    $this->typeFQCN ? DateTimeType::class : 'datetime',
                    [],
                    Guess::HIGH_CONFIDENCE
                );
            case 'float':
                return new TypeGuess(
                    $this->typeFQCN ? NumberType::class : 'number',
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'int':
            case 'integer':
                return new TypeGuess(
                    $this->typeFQCN ? IntegerType::class : 'integer',
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'string':
                return new TypeGuess(
                    $this->typeFQCN ? TextType::class : 'text',
                    [],
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
                return new ValueGuess($mapping['length'], Guess::HIGH_CONFIDENCE);
            }

            if ('float' === $mapping['type']) {
                return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function guessMinLength($class, $property)
    {
    }

    /**
     * @inheritDoc
     */
    public function guessPattern($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret[0]->hasField($property) && !$ret[0]->hasAssociation($property)) {
            $mapping = $ret[0]->getFieldMapping($property);

            if ('float' === $mapping['type']) {
                return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }
    }

    protected function getMetadata($class)
    {
        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $this->cache[$class] = null;
        foreach ($this->registry->getManagers() as $name => $dm) {
            try {
                return $this->cache[$class] = [$dm->getClassMetadata($class), $name];
            } catch (MappingException $e) {
                // not an entity or mapped super class
            }
        }
    }
}

