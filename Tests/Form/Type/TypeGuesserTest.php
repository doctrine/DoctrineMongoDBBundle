<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Symfony\Component\Form\Test\TypeTestCase;

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

    public function setUp()
    {
        $this->dm = TestCase::createTestDocumentManager(array(
            __DIR__ . '/../../Fixtures/Form/Guesser',
        ));
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    protected function tearDown()
    {
        $documentClasses = array(
            'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document',
            'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category',
            'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Guesser',
        );

        foreach ($documentClasses as $class) {
            $this->dm->getDocumentCollection($class)->drop();
        }

        parent::tearDown();
    }


    public function testTypesShouldBeGuessedCorrectly()
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
    }

    protected function assertType($type, $form)
    {
        $this->assertEquals($type, $form->getConfig()->getType()->getBlockPrefix());
    }

    protected function createRegistryMock($name, $dm)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
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
        return array_merge(parent::getExtensions(), array(
            new DoctrineMongoDBExtension($this->dmRegistry),
        ));
    }
}
