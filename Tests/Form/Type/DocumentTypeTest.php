<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Symfony\Component\Form\Tests\Extension\Core\Type\TypeTestCase;

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

    public function setUp()
    {
        $this->dm = TestCase::createTestDocumentManager(array(
            __DIR__ . '/../../Fixtures/Form/Document',
        ));
        $this->dmRegistry = $this->createRegistryMock('default', $this->dm);

        parent::setUp();
    }

    public function testDocumentManagerOptionSetsEmOption()
    {
        $field = $this->factory->createNamed('name', 'document', null, array(
            'class' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document',
            'document_manager' => 'default',
        ));

        $this->assertSame($this->dm, $field->getConfig()->getOption('em'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSettingDocumentManagerAndEmOptionShouldThrowException()
    {
        $field = $this->factory->createNamed('name', 'document', null, array(
            'document_manager' => 'default',
            'em' => 'default',
        ));
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
