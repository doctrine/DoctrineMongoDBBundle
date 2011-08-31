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

    /** @ODM\Hash */
    public $hash;

    /** @ODM\Collection */
    public $collection;

    public function __construct($id) {
        $this->id = $id;
    }
}
