<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Validator\Constraints;

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
    }
}

#[Unique(['email'])]
class UniqueDocumentDummyOne
{
    private string $email;
}
