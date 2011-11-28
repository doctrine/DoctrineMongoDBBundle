<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ItemGroupDocument
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\String */
    public $name;

    /** @ODM\String */
    public $groupName;

    public function __construct($id, $name, $groupName)
    {
        $this->id = $id;
        $this->name = $name;
        $this->groupName = $groupName;
    }
}