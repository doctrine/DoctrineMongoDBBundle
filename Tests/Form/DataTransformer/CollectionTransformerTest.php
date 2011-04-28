<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\CollectionTransformer;

class CollectionTransformerTest extends \PHPUnit_Framework_TestCase
{
    private $meta;
    private $repo;

    protected function setUp()
    {
        $this->meta = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\DocumentRepository')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider getCollectionizers
     */
    public function testTransform($collectionize)
    {
        $id = 'asdf1234';
        $document = new \stdClass();
        $document->id = $id;

        $ids = array($id);
        $collection = $collectionize(array($document));

        $this->meta->expects($this->once())
            ->method('getIdentifierValue')
            ->with($document)
            ->will($this->returnValue($id));

        $transformer = new CollectionTransformer($this->meta, $this->repo);
        $this->assertEquals($ids, $transformer->transform($collection), '->transform() transforms a collection of documents');
    }

    public function getCollectionizers()
    {
        return array(
            array(function(array $values) { return $values; }),
            array(function(array $values) { return new ArrayCollection($values); }),
        );
    }

    public function testReverseTransform()
    {
        $id = 'asdf1234';
        $document = new \stdClass();
        $document->id = $id;

        $ids = array($id);
        $collection = new ArrayCollection(array($document));

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($document));

        $transformer = new CollectionTransformer($this->meta, $this->repo);
        $this->assertEquals($collection, $transformer->reverseTransform($ids), '->reverseTransform() transforms an array of ids to a collection of documents');
    }

    public function testReverseTransformExistingCollection()
    {
        $id = 'asdf1234';
        $document = new \stdClass();
        $document->id = $id;

        $ids = array($id);
        $collection = $this->getMock('Doctrine\\Common\\Collections\\Collection');

        $collection->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue(array($document)));
        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($document));
        $collection->expects($this->once())
            ->method('contains')
            ->with($document)
            ->will($this->returnValue(true));
        $collection->expects($this->never())->method('add');
        $collection->expects($this->never())->method('remove');

        $transformer = new CollectionTransformer($this->meta, $this->repo, $collection);
        $transformer->reverseTransform($ids);
    }

    public function testReverseTransformExistingCollectionRemove()
    {
        $id = 'asdf1234';
        $document = new \stdClass();
        $document->id = $id;

        $ids = array();
        $collection = $this->getMock('Doctrine\\Common\\Collections\\Collection');

        $collection->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue(array($document)));
        $this->repo->expects($this->never())->method('find');
        $collection->expects($this->never())->method('contains');
        $collection->expects($this->never())->method('add');
        $collection->expects($this->once())
            ->method('remove')
            ->with($document);

        $transformer = new CollectionTransformer($this->meta, $this->repo, $collection);
        $transformer->reverseTransform($ids);
    }

    public function testReverseTransformNotFound()
    {
        $this->setExpectedException('Symfony\\Component\\Form\\Exception\\TransformationFailedException');

        $id = 'asdf1234';
        $ids = array($id);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $transformer = new CollectionTransformer($this->meta, $this->repo);
        $transformer->reverseTransform($ids);
    }
}
