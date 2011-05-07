<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form;

/** @Document */
class Document
{
    /** @Id(strategy="none") */
    protected $id;

    /** @String */
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
