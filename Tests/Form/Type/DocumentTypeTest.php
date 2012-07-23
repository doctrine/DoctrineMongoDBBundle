<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Henrik Bjornskov <henrik@bjrnskov.dk>
 */
class DocumentTypeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->classMetadata
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Document'))
        ;

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->classMetadata))
        ;

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->manager))
        ;

        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->manager))
        ;

        $this->type = new DocumentType($this->registry);
    }

    public function testDocumentManagerIsReturnedWhenGettingEm()
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options = $resolver->resolve(array(
            'document_manager' => 'document_manager',
        ));

        $this->assertInstanceOf('Doctrine\Common\Persistence\ObjectManager', $options['em']);
    }

    public function testExceptionWhenDocumentManagerAndEmIsSet()
    {
        $this->setExpectedException('InvalidArgumentException');

        $resolver = new OptionsResolver();

        $this->type->setDefaultOptions($resolver);

        $options = $resolver->resolve(array(
            'document_manager' => 'document_manager',
            'em' => 'entity_manager',
        ));
    }
}
