<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Validator\Constraints;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;

final class UniqueTest extends TestCase
{
    public function testWithDefaultProperty(): void
    {
        $metadata = new ClassMetadata(UniqueDocumentDummyOne::class);

        $loader = new AttributeLoader();

        self::assertTrue($loader->loadClassMetadata($metadata));

        [$constraint] = $metadata->getConstraints();
        self::assertInstanceOf(Unique::class, $constraint);
        self::assertSame(['email'], $constraint->fields);
        self::assertSame('doctrine_odm.mongodb.unique', $constraint->validatedBy());
        self::assertNull($constraint->em);
    }

    public function testWithGroups(): void
    {
        $metadata = new ClassMetadata(UniqueDocumentWithGroups::class);

        $loader = new AttributeLoader();
        self::assertTrue($loader->loadClassMetadata($metadata));

        [$constraint] = $metadata->getConstraints();
        self::assertInstanceOf(Unique::class, $constraint);
        self::assertSame(['email'], (array) $constraint->fields);
        self::assertSame(['group1'], $constraint->groups);
        self::assertSame('doctrine_odm.mongodb.unique', $constraint->validatedBy());
        self::assertNull($constraint->em);
    }

    public function testWithIdentifierFieldNames(): void
    {
        if (! InstalledVersions::satisfies(new VersionParser(), 'symfony/doctrine-bridge', '>= 7.3')) {
            self::markTestSkipped('Requires symfony/doctrine-bridge 7.3 or higher, with $identifierFieldNames field');
        }

        $metadata = new ClassMetadata(UniqueDocumentWithIdentifierFieldNames::class);

        $loader = new AttributeLoader();

        self::assertTrue($loader->loadClassMetadata($metadata));

        [$constraint] = $metadata->getConstraints();
        self::assertInstanceOf(Unique::class, $constraint);
        self::assertSame(['email'], $constraint->fields);
        self::assertSame('Custom message', $constraint->message);
        self::assertSame('custom_em', $constraint->em);
        self::assertFalse($constraint->ignoreNull);
        self::assertSame(['id', 'name'], $constraint->identifierFieldNames);
        self::assertSame(['group1', 'group2'], $constraint->groups);
        self::assertSame('doctrine_odm.mongodb.unique', $constraint->validatedBy());
    }
}

#[Unique(['email'])]
class UniqueDocumentDummyOne
{
    public string $email;
}

#[Unique(fields: 'email', groups: ['group1'])]
class UniqueDocumentWithGroups
{
    public string $email;
}

#[Unique(
    ['email'],
    'Custom message',
    em: 'custom_em',
    ignoreNull: false,
    identifierFieldNames: ['id', 'name'],
    groups: ['group1', 'group2'],
)]
class UniqueDocumentWithIdentifierFieldNames
{
    public string $name;

    public string $email;
}
