<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentsToArrayTransformer;

class DocumentsToArrayTransformerTest extends \PHPUnit_Framework_TestCase
{
    private $transformer;
    private $choiceList;

    protected function setUp()
    {
        $this->choiceList = $this->getMockChoiceList();
        $this->transformer = new DocumentsToArrayTransformer($this->choiceList);
    }

    public function testShouldTransformCollection()
    {
        $this->assertEquals(array(), $this->transformer->transform(null));
        $this->assertEquals(array(), $this->transformer->transform(array()));
        $this->assertEquals(array(), $this->transformer->transform(new ArrayCollection()));
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\UnexpectedTypeException
     */
    public function testShouldThrowWhenTransformingInvalidValue()
    {
        $this->transformer->transform(new \stdClass());
    }

    private function getMockChoiceList()
    {
        return $this->getMockBuilder('Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
    }
}

