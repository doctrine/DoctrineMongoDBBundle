<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Validator\Constraints;

use Symfony\Bundle\DoctrineMongoDBBundle\Tests\TestCase;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator\Document;
use Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\Unique;
use Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\UniqueValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator;

class UniqueValidatorTest extends TestCase
{
    const DEFAULT_DOCUMENT_MANAGER = 'doctrine.odm.mongodb.document_manager';

    private $documentManager;

    protected function setUp()
    {
        $this->documentManager = $this->createTestDocumentManager(array(
            __DIR__ . '/../DependencyInjection/Fixtures/Bundles/AnnotationsBundle/Document'
        ));

        $this->dropDocumentCollection();
    }

    protected function tearDown()
    {
        $this->dropDocumentCollection();
    }

    public function testValidateUniqueness()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('name');
        $validator = $this->createValidator($container, $constraint);

        $document1 = new Document(1, 'Foo');
        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document before it is saved to the database.');

        $this->documentManager->persist($document1);
        $this->documentManager->flush();

        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document after it was saved to the database.');

        $document2 = new Document(2, 'Foo');

        $violationsList = $validator->validate($document2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique value.');

        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('name', $violation->getPropertyPath());
        $this->assertEquals('Foo', $violation->getInvalidValue());
    }

    public function testValidateUniquenessWithNull()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('name');
        $validator = $this->createValidator($container, $constraint);

        $document1 = new Document(1, null);
        $document2 = new Document(2, null);

        $this->documentManager->persist($document1);
        $this->documentManager->persist($document2);
        $this->documentManager->flush();

        // Unlike SQL, MongoDB will consider two null values on a unique index as conflicting
        $violationsList = $validator->validate($document1);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique null value.');

        $violationsList = $validator->validate($document2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique null value.');
    }

    private function dropDocumentCollection()
    {
        $this->documentManager->getDocumentCollection('Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator\Document')->drop();
    }

    private function createMockContainer($documentManagerId = self::DEFAULT_DOCUMENT_MANAGER)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container->expects($this->any())
            ->method('get')
            ->with($documentManagerId)
            ->will($this->returnValue($this->documentManager));

        return $container;
    }

    private function createMockMetadataFactory($metadata)
    {
        $metadataFactory = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataFactoryInterface');
        $metadataFactory->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->equalTo($metadata->name))
            ->will($this->returnValue($metadata));

        return $metadataFactory;
    }

    private function createMockValidatorFactory($uniqueValidator)
    {
        $validatorFactory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
        $validatorFactory->expects($this->any())
             ->method('getInstance')
             ->with($this->isInstanceOf('Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\Unique'))
             ->will($this->returnValue($uniqueValidator));

        return $validatorFactory;
    }

    private function createValidator(ContainerInterface $container, Unique $constraint)
    {
        $uniqueValidator = new UniqueValidator($container);

        $metadata = new ClassMetadata('Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator\Document');
        $metadata->addConstraint($constraint);

        $metadataFactory = $this->createMockMetadataFactory($metadata);
        $validatorFactory = $this->createMockValidatorFactory($uniqueValidator);

        return new Validator($metadataFactory, $validatorFactory);
    }
}
