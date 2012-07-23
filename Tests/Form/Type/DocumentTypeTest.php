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
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->type = new DocumentType($this->registry);
    }

    public function testDocumentManagerIsReturnedWhenGettingEm()
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options = $resolver->resolve(array(
            'document_manager' => 'document_manager',
        ));

        $this->assertEquals('document_manager', $options['em']);
    }

    public function testExceptionWhenDocumentManagerAndEmIsSet()
    {
        $this->setExpectedException('InvalidArgumentException');

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'em' => 'entity_manager',
        ));

        $this->type->setDefaultOptions($resolver);
        $options = $resolver->resolve(array(
            'document_manager' => 'document_manager',
        ));

    }
}
