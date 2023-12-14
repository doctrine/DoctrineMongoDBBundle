<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Form;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
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
    /** @var array<class-string, array{ClassMetadata, string}|null> */
    private array $cache = [];

    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function guessType(string $class, string $property): ?TypeGuess
    {
        $ret = $this->getMetadata($class);
        if (! $ret) {
            return null;
        }

        [$metadata, $name] = $ret;

        if (! $metadata->hasField($property)) {
            return null;
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
                Guess::HIGH_CONFIDENCE,
            );
        }

        $fieldMapping = $metadata->getFieldMapping($property);
        switch ($fieldMapping['type']) {
            case Type::COLLECTION:
                return new TypeGuess(
                    CollectionType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE,
                );

            case Type::BOOL:
            case Type::BOOLEAN:
                return new TypeGuess(
                    CheckboxType::class,
                    [],
                    Guess::HIGH_CONFIDENCE,
                );

            case Type::DATE:
            case Type::TIMESTAMP:
                return new TypeGuess(
                    DateTimeType::class,
                    [],
                    Guess::HIGH_CONFIDENCE,
                );

            case Type::FLOAT:
                return new TypeGuess(
                    NumberType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE,
                );

            case Type::INT:
            case Type::INTEGER:
                return new TypeGuess(
                    IntegerType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE,
                );

            case Type::STRING:
                return new TypeGuess(
                    TextType::class,
                    [],
                    Guess::MEDIUM_CONFIDENCE,
                );
        }

        return null;
    }

    public function guessRequired(string $class, string $property): ?ValueGuess
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret[0]->hasField($property)) {
            if (! $ret[0]->isNullable($property)) {
                return new ValueGuess(
                    true,
                    Guess::HIGH_CONFIDENCE,
                );
            }

            return new ValueGuess(
                false,
                Guess::MEDIUM_CONFIDENCE,
            );
        }

        return null;
    }

    public function guessMaxLength(string $class, string $property): ?ValueGuess
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function guessMinLength($class, $property): void
    {
    }

    public function guessPattern(string $class, string $property): ?ValueGuess
    {
        $ret = $this->getMetadata($class);
        if (! $ret || ! $ret[0]->hasField($property) || $ret[0]->hasAssociation($property)) {
            return null;
        }

        $mapping = $ret[0]->getFieldMapping($property);

        if ($mapping['type'] === Type::FLOAT) {
            return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
        }

        return null;
    }

    /** @return array{ClassMetadata, string}|null */
    protected function getMetadata(string $class): ?array
    {
        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $this->cache[$class] = null;
        foreach ($this->registry->getManagers() as $name => $dm) {
            try {
                return $this->cache[$class] = [$dm->getClassMetadata($class), $name];
            } catch (MappingException) {
                // not an entity or mapped super class
            }
        }

        return null;
    }
}
