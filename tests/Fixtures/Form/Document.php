<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Document
{
    /**
     * @ODM\Id(strategy="none")
     *
     * @var ObjectId
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category",
     *     inversedBy="documents",
     *     strategy="atomicSetArray"
     * )
     *
     * @var Collection<int, Category>
     */
    public $categories;

    public function __construct(ObjectId $id, string $name)
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->categories = new ArrayCollection();
    }

    /**
     * Converts to string
     *
     * @return string
     **/
    public function __toString()
    {
        return $this->name;
    }
}
