<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Form\Type;

//require_once __DIR__.'/../../TestCase.php';
require_once __DIR__.'/../../Fixtures/Form/Document.php';

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Tests\Component\Form\Extension\Core\Type\TypeTestCase;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\TestCase;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\Document;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DoctrineMongoDBExtension;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Tests for DocumentType
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentTypeTest extends TypeTestCase
{
    const DOCUMENT_CLASS = 'Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\Document';

    private $documentManager;

    protected function setUp()
    {
        $this->documentManager = TestCase::createTestDocumentManager();
        $this->documentManager->createQueryBuilder(self::DOCUMENT_CLASS)
            ->remove()
            ->getQuery()
            ->execute();

        parent::setUp();
    }

    protected function getExtensions()
    {
        return array_merge(parent::getExtensions(), array(
            new DoctrineMongoDBExtension($this->documentManager),
        ));
    }

    protected function persist(array $documents)
    {
        foreach ($documents as $document) {
            $this->documentManager->persist($document);
        }

        $this->documentManager->flush();
        // no clear, because documents managed by the choice field must
        // be managed!
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\UnexpectedTypeException
     */
    public function testConfigureQueryBuilderWithNonQueryBuilderAndNonClosure()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => new \stdClass(),
        ));
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\UnexpectedTypeException
     */
    public function testConfigureQueryBuilderWithClosureReturningNonQueryBuilder()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => function () {
                return new \stdClass();
            },
        ));

        $field->bind('2');
    }

    public function testSetDataSingleNull()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => false,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->setData(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals('', $field->getClientData());
    }

    public function testSetDataMultipleExpandedNull()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => true,
            'expanded' => true,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->setData(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals(array(), $field->getClientData());
    }

    public function testSetDataMultipleNonExpandedNull()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => true,
            'expanded' => false,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->setData(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals(array(), $field->getClientData());
    }

    public function testSubmitSingleExpandedNull()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => false,
            'expanded' => true,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->bind(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals(array(), $field->getClientData());
    }

    public function testSubmitSingleNonExpandedNull()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => false,
            'expanded' => false,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->bind(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals('', $field->getClientData());
    }

    public function testSubmitMultipleNull()
    {
        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => true,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->bind(null);

        $this->assertEquals(new ArrayCollection(), $field->getData());
        $this->assertEquals(array(), $field->getClientData());
    }

    public function testSubmitSingleNonExpandedSingleIdentifier()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');

        $this->persist(array($document1, $document2));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => false,
            'expanded' => false,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->bind('2');

        $this->assertTrue($field->isSynchronized());
        $this->assertEquals($document2, $field->getData());
        $this->assertEquals(2, $field->getClientData());
    }

    public function testSubmitMultipleNonExpandedSingleIdentifier()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => true,
            'expanded' => false,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->bind(array('1', '3'));

        $expected = new ArrayCollection(array($document1, $document3));

        $this->assertTrue($field->isSynchronized());
        $this->assertEquals($expected, $field->getData());
        $this->assertEquals(array(1, 3), $field->getClientData());
    }

    public function testSubmitMultipleNonExpandedSingleIdentifier_existingData()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => true,
            'expanded' => false,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $existing = new ArrayCollection(array($document2));

        $field->setData($existing);
        $field->bind(array('1', '3'));

        // entry with index 0 was removed
        $expected = new ArrayCollection(array(1 => $document1, 2 => $document3));

        $this->assertTrue($field->isSynchronized());
        $this->assertEquals($expected, $field->getData());
        // same object still, useful if it is a PersistentCollection
        $this->assertSame($existing, $field->getData());
        $this->assertEquals(array(1, 3), $field->getClientData());
    }

    public function testSubmitSingleExpanded()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');

        $this->persist(array($document1, $document2));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => false,
            'expanded' => true,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->bind('2');

        $this->assertTrue($field->isSynchronized());
        $this->assertEquals($document2, $field->getData());
        $this->assertSame(false, $field['1']->getData());
        $this->assertSame(true, $field['2']->getData());
        $this->assertSame('', $field['1']->getClientData());
        $this->assertSame('1', $field['2']->getClientData());
    }

    public function testSubmitMultipleExpanded()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Bar');

        $this->persist(array($document1, $document2, $document3));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'multiple' => true,
            'expanded' => true,
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->bind(array('1' => '1', '3' => '3'));

        $expected = new ArrayCollection(array($document1, $document3));

        $this->assertTrue($field->isSynchronized());
        $this->assertEquals($expected, $field->getData());
        $this->assertSame(true, $field['1']->getData());
        $this->assertSame(false, $field['2']->getData());
        $this->assertSame(true, $field['3']->getData());
        $this->assertSame('1', $field['1']->getClientData());
        $this->assertSame('', $field['2']->getClientData());
        $this->assertSame('1', $field['3']->getClientData());
    }

    public function testOverrideChoices()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            // not all persisted documents should be displayed
            'choices' => array($document1, $document2),
            'property' => 'name',
        ));

        $field->bind('2');

        $this->assertEquals(array(1 => 'Foo', 2 => 'Bar'), $field->createView()->get('choices'));
        $this->assertTrue($field->isSynchronized());
        $this->assertEquals($document2, $field->getData());
        $this->assertEquals(2, $field->getClientData());
    }

    public function testDisallowChoicesThatAreNotIncluded_choicesSingleIdentifier()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'choices' => array($document1, $document2),
            'property' => 'name',
        ));

        $field->bind('3');

        $this->assertFalse($field->isSynchronized());
        $this->assertNull($field->getData());
    }

    public function testDisallowChoicesThatAreNotIncludedQueryBuilderSingleIdentifier()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $repository = $this->documentManager->getRepository(self::DOCUMENT_CLASS);

        $field = $this->factory->createNamed('document', 'name', null, array(
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => $repository->createQueryBuilder()
                ->field('id')->in(array(1,2)),
            'property' => 'name',
        ));

        $field->bind('3');

        $this->assertFalse($field->isSynchronized());
        $this->assertNull($field->getData());
    }

    public function testDisallowChoicesThatAreNotIncludedQueryBuilderAsClosureSingleIdentifier()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');
        $document3 = new Document(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = $this->factory->createNamed('document', 'name', null, array(
            'document_manager' => $this->documentManager,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => function ($repository) {
                return $repository->createQueryBuilder()
                        ->field('id')->in(array(1, 2));
            },
            'property' => 'name',
        ));

        $field->bind('3');

        $this->assertFalse($field->isSynchronized());
        $this->assertNull($field->getData());
    }
}
