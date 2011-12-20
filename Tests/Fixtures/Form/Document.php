<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Document
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\String */
    public $name;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
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
