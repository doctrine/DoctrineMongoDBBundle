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
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument;
use Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\Unique;
use Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\UniqueValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Mapping\ClassMetadata;

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

    public function testValidateUniquenessForScalarField()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('name');
        $validator = $this->createValidator($container, $constraint);

        $document1 = new Document(1);
        $document1->name = 'Foo';
        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document before it is saved to the database.');

        $this->documentManager->persist($document1);
        $this->documentManager->flush();

        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document after it was saved to the database.');

        $document2 = new Document(2);
        $document2->name = 'Foo';

        $violationsList = $validator->validate($document2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique value.');

        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('name', $violation->getPropertyPath());
        $this->assertEquals('Foo', $violation->getInvalidValue());
    }

    public function testValidateUniquenessForScalarFieldWithNull()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('name');
        $validator = $this->createValidator($container, $constraint);

        $document1 = new Document(1);
        $document2 = new Document(2);

        $this->documentManager->persist($document1);
        $this->documentManager->persist($document2);
        $this->documentManager->flush();

        // Unlike SQL, MongoDB will consider two null values on a unique index as conflicting
        $violationsList = $validator->validate($document1);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique null value.');

        $violationsList = $validator->validate($document2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique null value.');
    }

    public function testValidateUniquenessForCollectionField()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('collection');
        $validator = $this->createValidator($container, $constraint);

        $document1 = new Document(1);
        $document1->collection = array('a', 'b', 'c');
        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document before it is saved to the database.');

        $this->documentManager->persist($document1);
        $this->documentManager->flush();

        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document after it was saved to the database.');

        $document2 = new Document(2);
        $document2->collection = array('b');

        $violationsList = $validator->validate($document2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique value in a collection field.');

        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('collection', $violation->getPropertyPath());
        $this->assertEquals(array('b'), $violation->getInvalidValue());
    }

    public function testValidateUniquenessForHashField()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('hash.foo');
        $validator = $this->createValidator($container, $constraint);

        $document1 = new Document(1);
        $document1->hash = array('foo' => 'bar');
        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document before it is saved to the database.');

        $this->documentManager->persist($document1);
        $this->documentManager->flush();

        $violationsList = $validator->validate($document1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on document after it was saved to the database.');

        $document2 = new Document(2);
        $document2->hash = array('foo' => 'bar');

        $violationsList = $validator->validate($document2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on document due to non-unique value in a hash field.');

        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('hash.foo', $violation->getPropertyPath());
        $this->assertEquals('bar', $violation->getInvalidValue());
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testShouldThrowExceptionForUnmappedField()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('unmappedField');
        $validator = $this->createValidator($container, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testReferenceFieldTypeIsUnsupported()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('referenceOne');
        $validator = $this->createValidator($container, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testEmbedManyFieldTypeIsUnsupported()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('embedMany');
        $validator = $this->createValidator($container, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testEmbedOneFieldTypeIsUnsupported()
    {
        $container = $this->createMockContainer();
        $constraint = new Unique('embedOne');
        $validator = $this->createValidator($container, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testEmbeddedDocumentIsNotUnsupported()
    {
        require_once __DIR__.'/../../Fixtures/Validator/Document.php';

        $container = $this->createMockContainer();
        $constraint = new Unique('name');

        $uniqueValidator = new UniqueValidator($container);

        $metadata = new ClassMetadata('Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument');
        $metadata->addConstraint($constraint);

        $metadataFactory = $this->createMockMetadataFactory($metadata);
        $validatorFactory = $this->createMockValidatorFactory($uniqueValidator);

        $validator = new Validator($metadataFactory, $validatorFactory);

        $violationsList = $validator->validate(new EmbeddedDocument());
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
