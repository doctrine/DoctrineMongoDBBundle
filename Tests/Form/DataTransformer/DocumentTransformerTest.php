<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Form\DataTransformer;

use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentTransformer;

class DocumentTransformerTest extends \PHPUnit_Framework_TestCase
{
    private $meta;
    private $repo;
    private $transformer;

    protected function setUp()
    {
        $this->meta = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder('Doctrine\\ODM\\MongoDB\\DocumentRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transformer = new DocumentTransformer($this->meta, $this->repo);
    }

    public function testTransform()
    {
        $id = 'asdf1234';
        $document = new \stdClass();
        $document->id = $id;

        $this->meta->expects($this->once())
            ->method('getIdentifierValue')
            ->with($document)
            ->will($this->returnValue($id));

        $this->assertEquals($id, $this->transformer->transform($document), '->transform() transforms a document to an id');
    }

    /**
     * @dataProvider getEmpties
     */
    public function testTransformEmpty($empty)
    {
        $this->assertSame('', $this->transformer->transform($empty), '->transform() transforms an empty value to an empty string');
    }

    /**
     * @dataProvider getEmpties
     */
    public function testReverseTransformEmpty($empty)
    {
        $this->assertSame(null, $this->transformer->reverseTransform($empty), '->reverseTransform() transforms an empty value to null');
    }

    public function getEmpties()
    {
        return array(
            array(null),
            array(''),
        );
    }

    public function testReverseTransform()
    {
        $id = 'asdf1234';
        $document = new \stdClass();
        $document->id = $id;

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($document));

        $this->assertEquals($document, $this->transformer->reverseTransform($id), '->reverseTransform() transforms an id to a document');
    }

    public function testReverseTransformNotFound()
    {
        $this->setExpectedException('Symfony\\Component\\Form\\Exception\\TransformationFailedException');

        $id = 'asdf1234';

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $this->transformer->reverseTransform($id);
    }
}
