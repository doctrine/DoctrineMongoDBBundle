<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpKernel\Kernel;

class DocumentTypeTest extends TypeTestCase
{
    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dmRegistry;

    private $typeFQCN;

    public function setUp()
    {
        $this->typeFQCN = method_exists('\Symfony\Component\Form\AbstractType', 'getBlockPrefix');

        $this->dm = TestCase::createTestDocumentManager(array(
            __DIR__ . '/../../Fixtures/Form/Document',
        ));
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    protected function tearDown()
    {
        $documentClasses = array(
            'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document',
            'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category',
        );

        foreach ($documentClasses as $class) {
            $this->dm->getDocumentCollection($class)->drop();
        }

        parent::tearDown();
    }


    public function testDocumentManagerOptionSetsEmOption()
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::CLASS : 'document', null, array(
            'class' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document',
            'document_manager' => 'default',
        ));

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    public function testDocumentManagerInstancePassedAsOption()
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::CLASS : 'document', null, array(
            'class' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document',
            'document_manager' => $this->dm,
        ));

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSettingDocumentManagerAndEmOptionShouldThrowException()
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::CLASS : 'document', null, array(
            'document_manager' => 'default',
            'em' => 'default',
        ));
    }

    public function testManyToManyReferences()
    {
        $categoryOne = new Category('one');
        $this->dm->persist($categoryOne);
        $categoryTwo = new Category('two');
        $this->dm->persist($categoryTwo);

        $document = new Document(new \MongoId(), 'document');
        $document->categories[] = $categoryOne;
        $this->dm->persist($document);

        $this->dm->flush();

        $form = $this->factory->create($this->typeFQCN ? FormType::CLASS : 'form', $document)
            ->add(
                'categories', $this->typeFQCN ? DocumentType::CLASS : 'document', array(
                    'class' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category',
                    'multiple' => true,
                    'expanded' => true,
                    'document_manager' => 'default'
                )
            );

        $view = $form->createView();
        $categoryView = $view['categories'];
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $categoryView);

        $this->assertCount(2, $categoryView->children);
        $this->assertTrue($categoryView->children[0]->vars['checked']);
        $this->assertFalse($categoryView->children[1]->vars['checked']);
    }

    protected function createRegistryMock($name, $dm)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
                 ->method('getManager')
                 ->with($this->equalTo($name))
                 ->will($this->returnValue($dm));

        return $registry;
    }

    /**
     * @see Symfony\Component\Form\Tests\FormIntegrationTestCase::getExtensions()
     */
    protected function getExtensions()
    {
        return array_merge(parent::getExtensions(), array(
            new DoctrineMongoDBExtension($this->dmRegistry),
        ));
    }
}
