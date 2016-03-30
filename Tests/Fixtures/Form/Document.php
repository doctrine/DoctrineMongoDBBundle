<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Document
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category",
     *     inversedBy="documents"
     * )
     */
    public $categories;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
        $this->categories = new ArrayCollection();
    }

    /**
     * Converts to string
     *
     * @return string
     **/
    public function __toString()
    {
        return (string) $this->name;
    }
}
