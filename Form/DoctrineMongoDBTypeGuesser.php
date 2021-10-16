<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Form;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
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

use function array_key_exists;

/**
 * Tries to guess form types according to ODM mappings
 */
class DoctrineMongoDBTypeGuesser implements FormTypeGuesserInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var array */
    private $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return TypeGuess|null
     */
    public function guessType($class, $property)
    {
        $ret = $this->getMetadata($class);
        if (! $ret) {
            return;
        }

        [$metadata, $name] = $ret;

        if (! $metadata->hasField($property)) {
            return;
        }

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping  = $metadata->getFieldMapping($property);

            return new TypeGuess(
                DocumentType::class,
                [
                    'class' => $mapping['targetDocument'],
                    'multiple' => $multiple,
                    'expanded' => $multiple,
                ],
                Guess::HIGH_CONFIDENCE
            );
        }

        $fieldMapping = $metadata->getFieldMapping($property);
        switch ($fieldMapping['type']) {
            case 'collection':
                return new TypeGuess(
                    CollectionType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );

            case 'bool':
            case 'boolean':
                return new TypeGuess(
                    CheckboxType::class,
                    [],
                    Guess::HIGH_CONFIDENCE
                );

            case 'date':
            case 'timestamp':
                return new TypeGuess(
                    DateTimeType::class,
                    [],
                    Guess::HIGH_CONFIDENCE
                );

            case 'float':
                return new TypeGuess(
                    NumberType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );

            case 'int':
            case 'integer':
                return new TypeGuess(
                    IntegerType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );

            case 'string':
                return new TypeGuess(
                    TextType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE
                );
        }
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return ValueGuess|null
     */
    public function guessRequired($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret[0]->hasField($property)) {
            if (! $ret[0]->isNullable($property)) {
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
     * @param string $class
     * @param string $property
     *
     * @return ValueGuess|null
     */
    public function guessMaxLength($class, $property)
    {
        $ret = $this->getMetadata($class);
        if (! $ret || ! $ret[0]->hasField($property) || $ret[0]->hasAssociation($property)) {
            return;
        }

        $mapping = $ret[0]->getFieldMapping($property);

        if (isset($mapping['length'])) {
            return new ValueGuess($mapping['length'], Guess::HIGH_CONFIDENCE);
        }

        if ($mapping['type'] === 'float') {
            return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function guessMinLength($class, $property)
    {
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return ValueGuess|null
     */
    public function guessPattern($class, $property)
    {
        $ret = $this->getMetadata($class);
        if (! $ret || ! $ret[0]->hasField($property) || $ret[0]->hasAssociation($property)) {
            return;
        }

        $mapping = $ret[0]->getFieldMapping($property);

        if ($mapping['type'] === 'float') {
            return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
        }
    }

    /**
     * @param string $class
     *
     * @return array{ClassMetadata, string}|null
     */
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
