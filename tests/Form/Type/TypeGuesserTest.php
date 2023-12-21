<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Guesser;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;

use function array_merge;

class TypeGuesserTest extends TypeTestCase
{
    private DocumentManager $dm;

    /** @var MockObject&ManagerRegistry */
    private ManagerRegistry $dmRegistry;

    protected function setUp(): void
    {
        $this->dm         = TestCase::createTestDocumentManager([
            __DIR__ . '/../../Fixtures/Form/Guesser',
        ]);
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $documentClasses = [
            Document::class,
            Category::class,
            Guesser::class,
        ];

        foreach ($documentClasses as $class) {
            $this->dm->getDocumentCollection($class)->drop();
        }

        parent::tearDown();
    }

    public function testTypesShouldBeGuessedCorrectly(): void
    {
        $form = $this->factory->create(GuesserTestType::class, null, ['dm' => $this->dm]);
        $this->assertType('text', $form->get('name'));
        $this->assertType('document', $form->get('categories'));
        $this->assertType('datetime', $form->get('date'));
        $this->assertType('datetime', $form->get('ts'));
        $this->assertType('checkbox', $form->get('boolField'));
        $this->assertType('number', $form->get('floatField'));
        $this->assertType('integer', $form->get('intField'));
        $this->assertType('collection', $form->get('collectionField'));
        $this->assertType('text', $form->get('nonMappedField'));
    }

    protected function assertType(string $type, FormInterface $form): void
    {
        $this->assertEquals($type, $form->getConfig()->getType()->getBlockPrefix());
    }

    /** @return MockObject&ManagerRegistry */
    protected function createRegistryMock(string $name, DocumentManager $dm): MockObject
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManager')
            ->with($this->equalTo($name))
            ->willReturn($dm);
        $registry
            ->method('getManagers')
            ->willReturn(['default' => $dm]);

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
