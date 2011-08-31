<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="DoctrineMongoDBBundle_Tests_Validator_Document") */
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
}
