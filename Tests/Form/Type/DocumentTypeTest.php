<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

use function array_merge;
use function method_exists;

class DocumentTypeTest extends TypeTestCase
{
    /** @var DocumentManager */
    private $dm;

    /** @var MockObject */
    private $dmRegistry;

    private $typeFQCN;

    protected function setUp(): void
    {
        $this->typeFQCN = method_exists(AbstractType::class, 'getBlockPrefix');

        $this->dm         = TestCase::createTestDocumentManager([
            __DIR__ . '/../../Fixtures/Form/Document',
        ]);
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    protected function tearDown(): void
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

    public function testDocumentManagerOptionSetsEmOption(): void
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::class : 'document', null, [
            'class' => Document::class,
            'document_manager' => 'default',
        ]);

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    public function testDocumentManagerInstancePassedAsOption(): void
    {
        $field = $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::class : 'document', null, [
            'class' => Document::class,
            'document_manager' => $this->dm,
        ]);

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    public function testSettingDocumentManagerAndEmOptionShouldThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createNamed('name', $this->typeFQCN ? DocumentType::class : 'document', null, [
            'document_manager' => 'default',
            'em' => 'default',
        ]);
    }

    public function testManyToManyReferences(): void
    {
        $categoryOne = new Category('one');
        $this->dm->persist($categoryOne);
        $categoryTwo = new Category('two');
        $this->dm->persist($categoryTwo);

        $document               = new Document(new ObjectId(), 'document');
        $document->categories[] = $categoryOne;
        $this->dm->persist($document);

        $this->dm->flush();

        $form = $this->factory->create($this->typeFQCN ? FormType::class : 'form', $document)
            ->add(
                'categories',
                $this->typeFQCN ? DocumentType::class : 'document',
                [
                    'class' => Category::class,
                    'multiple' => true,
                    'expanded' => true,
                    'document_manager' => 'default',
                ]
            );

        $view         = $form->createView();
        $categoryView = $view['categories'];
        $this->assertInstanceOf(FormView::class, $categoryView);

        $this->assertCount(2, $categoryView->children);
        $this->assertTrue($categoryView->children[0]->vars['checked']);
        $this->assertFalse($categoryView->children[1]->vars['checked']);
    }

    /**
     * @return MockObject&ManagerRegistry
     */
    protected function createRegistryMock(string $name, DocumentManager $dm): MockObject
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
                 ->method('getManager')
                 ->with($this->equalTo($name))
                 ->willReturn($dm);

        return $registry;
    }

    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions()
    {
        return array_merge(parent::getExtensions(), [
            new DoctrineMongoDBExtension($this->dmRegistry),
        ]);
    }
}
