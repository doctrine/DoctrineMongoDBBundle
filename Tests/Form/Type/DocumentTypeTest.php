<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;
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
        $this->typeFQCN = method_exists(AbstractType::class, 'getBlockPrefix');

        $this->dm = TestCase::createTestDocumentManager([
            __DIR__ . '/../../Fixtures/Form/Document',
        ]);
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    protected function tearDown()
    {
        $documentClasses = [
            Document::class,
            Category::class,
        ];

        foreach ($documentClasses as $class) {
            $this->dm->getDocumentCollection($class)->drop();
        }

        parent::tearDown();
    }


    public function testDocumentManagerOptionSetsEmOption()
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::CLASS : 'document', null, [
            'class' => Document::class,
            'document_manager' => 'default',
        ]);

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    public function testDocumentManagerInstancePassedAsOption()
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::CLASS : 'document', null, [
            'class' => Document::class,
            'document_manager' => $this->dm,
        ]);

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSettingDocumentManagerAndEmOptionShouldThrowException()
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::CLASS : 'document', null, [
            'document_manager' => 'default',
            'em' => 'default',
        ]);
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
                'categories', $this->typeFQCN ? DocumentType::CLASS : 'document', [
                    'class' => Category::class,
                    'multiple' => true,
                    'expanded' => true,
                    'document_manager' => 'default'
                ]
            );

        $view = $form->createView();
        $categoryView = $view['categories'];
        $this->assertInstanceOf(FormView::class, $categoryView);

        $this->assertCount(2, $categoryView->children);
        $this->assertTrue($categoryView->children[0]->vars['checked']);
        $this->assertFalse($categoryView->children[1]->vars['checked']);
    }

    protected function createRegistryMock($name, $dm)
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
                 ->method('getManager')
                 ->with($this->equalTo($name))
                 ->will($this->returnValue($dm));

        return $registry;
    }

    /**
     * @see \Symfony\Component\Form\Tests\FormIntegrationTestCase::getExtensions()
     */
    protected function getExtensions()
    {
        return array_merge(parent::getExtensions(), [
            new DoctrineMongoDBExtension($this->dmRegistry),
        ]);
    }
}
