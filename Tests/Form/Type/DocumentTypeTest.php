<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\MongoDBBundle\Form\DoctrineMongoDBExtension;
use Symfony\Component\Form\Tests\Extension\Core\Type\TypeTestCase;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Playlist;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Video;

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
            __DIR__ . '/../../Fixtures/Form/Playlist',
            __DIR__ . '/../../Fixtures/Form/Video',
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

    public function testManyToManyReferences()
    {
        $videoOne = new Video();
        $this->dm->persist($videoOne);
        $videoTwo = new Video();
        $this->dm->persist($videoTwo);
        $this->dm->flush();

        $playlist = new Playlist();
        $playlist->addVideo($videoOne);
        $playlist->addVideo($videoTwo);
        $this->dm->persist($playlist);
        $this->dm->flush();

        $this->assertCount(2, $playlist->getVideos());
        $this->assertCount(1, $videoOne->getPlaylists());
        $this->assertCount(1, $videoTwo->getPlaylists());
        $this->assertEquals(1, $videoOne->getNbPlaylists());
        $this->assertEquals(1, $videoTwo->getNbPlaylists());

        $form = $this->factory->create('document', $playlist->getVideos(), [
            'class' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Video',
            'multiple' => true,
            'by_reference' => false,
            'document_manager' => 'default',
        ]);

        $form->submit([]);
        $this->dm->flush();

        $this->assertCount(0, $form->getData());
        $this->assertCount(0, $playlist->getVideos());
        $this->assertCount(0, $videoOne->getPlaylists());
        $this->assertCount(0, $videoTwo->getPlaylists());
        $this->assertEquals(0, $videoOne->getNbPlaylists());
        $this->assertEquals(0, $videoTwo->getNbPlaylists());
    }
}
