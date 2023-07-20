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
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

use function array_merge;

class DocumentTypeTest extends TypeTestCase
{
    private DocumentManager $dm;

    /** @var MockObject&ManagerRegistry */
    private ManagerRegistry $dmRegistry;

    protected function setUp(): void
    {
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
        $field = $this->factory->createNamed('name', DocumentType::class, null, [
            'class' => Document::class,
            'document_manager' => 'default',
        ]);

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    public function testDocumentManagerInstancePassedAsOption(): void
    {
        $field = $this->factory->createNamed('name', DocumentType::class, null, [
            'class' => Document::class,
            'document_manager' => $this->dm,
        ]);

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    public function testSettingDocumentManagerAndEmOptionShouldThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createNamed('name', DocumentType::class, null, [
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

        $form = $this->factory->create(FormType::class, $document)
            ->add(
                'categories',
                DocumentType::class,
                [
                    'class' => Category::class,
                    'multiple' => true,
                    'expanded' => true,
                    'document_manager' => 'default',
                ],
            );

        $view         = $form->createView();
        $categoryView = $view['categories'];
        $this->assertInstanceOf(FormView::class, $categoryView);

        $this->assertCount(2, $categoryView->children);
        $this->assertTrue($form->get('categories')->get('0')->createView()->vars['checked']);
        $this->assertFalse($form->get('categories')->get('1')->createView()->vars['checked']);
    }

    /** @return MockObject&ManagerRegistry */
    protected function createRegistryMock(string $name, DocumentManager $dm): MockObject
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
                 ->method('getManager')
                 ->with($this->equalTo($name))
                 ->willReturn($dm);

        return $registry;
    }

    /** @return FormExtensionInterface[] */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new DoctrineMongoDBExtension($this->dmRegistry),
        ]);
    }
}
