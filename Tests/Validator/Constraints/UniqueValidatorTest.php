<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Tests\Validator\Constraints;

use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\Document;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\UniqueValidator;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator;

class UniqueValidatorTest extends TestCase
{
    private $documentManager;

    protected function setUp()
    {
        parent::setUp();

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
        $registry = $this->createMockRegistry();
        $constraint = new Unique('name');
        $validator = $this->createValidator($registry, $constraint);

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
        $registry = $this->createMockRegistry();
        $constraint = new Unique('name');
        $validator = $this->createValidator($registry, $constraint);

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
        $registry = $this->createMockRegistry();
        $constraint = new Unique('collection');
        $validator = $this->createValidator($registry, $constraint);

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
        $registry = $this->createMockRegistry();
        $constraint = new Unique('hash.foo');
        $validator = $this->createValidator($registry, $constraint);

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
        $registry = $this->createMockRegistry();
        $constraint = new Unique('unmappedField');
        $validator = $this->createValidator($registry, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testReferenceFieldTypeIsUnsupported()
    {
        $registry = $this->createMockRegistry();
        $constraint = new Unique('referenceOne');
        $validator = $this->createValidator($registry, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testEmbedManyFieldTypeIsUnsupported()
    {
        $registry = $this->createMockRegistry();
        $constraint = new Unique('embedMany');
        $validator = $this->createValidator($registry, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testEmbedOneFieldTypeIsUnsupported()
    {
        $registry = $this->createMockRegistry();
        $constraint = new Unique('embedOne');
        $validator = $this->createValidator($registry, $constraint);

        $violationsList = $validator->validate(new Document(1));
    }

    /**
     * @expectedException Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testEmbeddedDocumentIsNotUnsupported()
    {
        require_once __DIR__.'/../../Fixtures/Validator/Document.php';

        $registry = $this->createMockRegistry();
        $constraint = new Unique('name');

        $uniqueValidator = new UniqueValidator($registry);

        $metadata = new ClassMetadata('Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\EmbeddedDocument');
        $metadata->addConstraint($constraint);

        $metadataFactory = $this->createMockMetadataFactory($metadata);
        $validatorFactory = $this->createMockValidatorFactory($uniqueValidator);

        $validator = new Validator($metadataFactory, $validatorFactory);

        $violationsList = $validator->validate(new EmbeddedDocument());
    }

    private function dropDocumentCollection()
    {
        if ($this->documentManager) {
            $this->documentManager->getDocumentCollection('Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\Document')->drop();
        }
    }

    private function createMockRegistry($documentManagerName = null)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->expects($this->any())
            ->method('getManager')
            ->with($documentManagerName)
            ->will($this->returnValue($this->documentManager));

        return $registry;
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
             ->with($this->isInstanceOf('Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique'))
             ->will($this->returnValue($uniqueValidator));

        return $validatorFactory;
    }

    private function createValidator(ManagerRegistry $registry, Unique $constraint)
    {
        $uniqueValidator = new UniqueValidator($registry);

        $metadata = new ClassMetadata('Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Validator\Document');
        $metadata->addConstraint($constraint);

        $metadataFactory = $this->createMockMetadataFactory($metadata);
        $validatorFactory = $this->createMockValidatorFactory($uniqueValidator);

        return new Validator($metadataFactory, $validatorFactory);
    }
}
