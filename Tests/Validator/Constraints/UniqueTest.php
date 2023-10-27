<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Validator\Constraints;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

use function assert;

final class UniqueTest extends TestCase
{
    public function testWithDefaultProperty(): void
    {
        $metadata = new ClassMetadata(UniqueDocumentDummyOne::class);

        $loader = new AnnotationLoader();

        self::assertTrue($loader->loadClassMetadata($metadata));

        [$constraint] = $metadata->getConstraints();
        assert($constraint instanceof Unique);
        self::assertSame(['email'], $constraint->fields);
        self::assertSame('doctrine_odm.mongodb.unique', $constraint->validatedBy());
    }
}

/** @Unique(fields={"email"}) */
#[Unique(['email'])]
class UniqueDocumentDummyOne
{
    private string $email;
}
