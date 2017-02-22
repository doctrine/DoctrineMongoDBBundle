<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Guesser;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Vladimir Chub <v@chub.com.ua>
 */
class TypeGuesserTest extends TypeTestCase
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
            __DIR__ . '/../../Fixtures/Form/Guesser',
        ]);
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    protected function tearDown()
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


    public function testTypesShouldBeGuessedCorrectly()
    {
        $form = $this->factory->create($this->typeFQCN ? GuesserTestType::class : new GuesserTestType(), null, ['dm' => $this->dm]);
        $this->assertType('text', $form->get('name'));
        $this->assertType('document', $form->get('categories'));
        $this->assertType('datetime', $form->get('date'));
        $this->assertType('datetime', $form->get('ts'));
        $this->assertType('checkbox', $form->get('boolField'));
        $this->assertType('checkbox', $form->get('booleanField'));
        $this->assertType('number', $form->get('floatField'));
        $this->assertType('integer', $form->get('intField'));
        $this->assertType('integer', $form->get('integerField'));
        $this->assertType('collection', $form->get('collectionField'));
    }

    protected function assertType($type, $form)
    {
        $this->assertEquals($type, $this->typeFQCN ? $form->getConfig()->getType()->getBlockPrefix() : $form->getConfig()->getType()->getName());
    }

    protected function createRegistryMock($name, $dm)
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($name))
            ->will($this->returnValue($dm));
        $registry->expects($this->any())
            ->method('getManagers')
            ->will($this->returnValue(['default' => $dm]));

        return $registry;
    }

    /**
     * @see Symfony\Component\Form\Tests\FormIntegrationTestCase::getExtensions()
     */
    protected function getExtensions()
    {
        return array_merge(parent::getExtensions(), [
            new DoctrineMongoDBExtension($this->dmRegistry),
        ]);
    }
}
