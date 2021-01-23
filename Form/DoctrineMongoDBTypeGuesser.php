<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Form;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
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

use function array_key_exists;
use function method_exists;

/**
 * Tries to guess form types according to ODM mappings
 */
class DoctrineMongoDBTypeGuesser implements FormTypeGuesserInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var array */
    private $cache = [];

    /** @var bool */
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
                $this->typeFQCN ? DocumentType::class : 'document',
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
     * @inheritDoc
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
     * @inheritDoc
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
