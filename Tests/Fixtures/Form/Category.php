<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Category
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document",
     *     mappedBy="categories"
     * )
     */
    public $documents;

    public function __construct($name)
    {
        $this->name = $name;
        $this->documents = new ArrayCollection();
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
