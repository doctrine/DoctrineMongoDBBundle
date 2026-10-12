<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Validator\Constraints;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Document\User;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Exists;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bridge\Doctrine\Validator\Constraints\EntityExists;
use Symfony\Bridge\Doctrine\Validator\Constraints\EntityExistsValidator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

use function class_exists;

class ExistsValidatorIntegrationTest extends TestCase
{
    private DocumentManager $dm;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        if (! class_exists(EntityExists::class)) {
            self::markTestSkipped('Requires symfony/doctrine-bridge 8.2 or higher');
        }

        $this->dm = self::createTestDocumentManager([__DIR__ . '/../../Fixtures/FooBundle/Document']);

        $container = new Container();
        $container->set('doctrine_mongodb.odm.default_document_manager', $this->dm);

        $registry = new ManagerRegistry(
            'MongoDB',
            [],
            ['default' => 'doctrine_mongodb.odm.default_document_manager'],
            'default',
            'default',
            LazyObjectInterface::class,
            $container,
        );

        // The "doctrine_odm.mongodb.exists" key is the name returned by Exists::validatedBy().
        $this->validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                'doctrine_odm.mongodb.exists' => new EntityExistsValidator($registry),
            ]))
            ->getValidator();
    }

    public function testExistingDocumentHasNoViolation(): void
    {
        $user = new User('user-identifier');
        $this->dm->persist($user);
        $this->dm->flush();

        $violations = $this->validator->validate('user-identifier', new Exists(entityClass: User::class));

        $this->assertCount(0, $violations);

        $this->dm->remove($user);
        $this->dm->flush();
    }

    public function testMissingDocumentHasViolation(): void
    {
        $violations = $this->validator->validate('unknown-identifier', new Exists(entityClass: User::class));

        $this->assertCount(1, $violations);
        $this->assertSame('unknown-identifier', $violations[0]->getInvalidValue());
    }
}
