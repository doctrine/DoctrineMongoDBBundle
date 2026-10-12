<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Validator\Constraints;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Exists;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\EntityExists;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

use function class_exists;

final class ExistsTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(EntityExists::class)) {
            self::markTestSkipped('Requires symfony/doctrine-bridge 8.2 or higher');
        }
    }

    public function testWithDefaultOptions(): void
    {
        $metadata = new ClassMetadata(ExistsDummyOne::class);

        $loader = new AttributeLoader();

        self::assertTrue($loader->loadClassMetadata($metadata));

        [$constraint] = $metadata->getPropertyMetadata('documentId')[0]->getConstraints();
        self::assertInstanceOf(Exists::class, $constraint);
        self::assertSame('App\\Document\\MyDocument', $constraint->entityClass);
        self::assertSame('doctrine_odm.mongodb.exists', $constraint->validatedBy());
        self::assertNull($constraint->em);
        self::assertNull($constraint->identifierField);
        self::assertNull($constraint->repositoryMethod);
    }

    public function testWithOptions(): void
    {
        $metadata = new ClassMetadata(ExistsDummyTwo::class);

        $loader = new AttributeLoader();

        self::assertTrue($loader->loadClassMetadata($metadata));

        [$constraint] = $metadata->getPropertyMetadata('identifier')[0]->getConstraints();
        self::assertInstanceOf(Exists::class, $constraint);
        self::assertSame('App\\Document\\MyDocument', $constraint->entityClass);
        self::assertSame('Custom message', $constraint->message);
        self::assertSame('custom_dm', $constraint->em);
        self::assertSame('uuid', $constraint->identifierField);
        self::assertNull($constraint->repositoryMethod);
        self::assertSame(['group1'], $constraint->groups);
        self::assertSame('doctrine_odm.mongodb.exists', $constraint->validatedBy());
    }
}

class ExistsDummyOne
{
    #[Exists(entityClass: 'App\\Document\\MyDocument')]
    public string $documentId;
}

class ExistsDummyTwo
{
    #[Exists(
        entityClass: 'App\\Document\\MyDocument',
        message: 'Custom message',
        em: 'custom_dm',
        identifierField: 'uuid',
        groups: ['group1'],
    )]
    public string $identifier;
}
