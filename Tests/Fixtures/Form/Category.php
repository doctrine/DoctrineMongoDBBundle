<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Category
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
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
     *     targetDocument="Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Document",
     *     mappedBy="categories"
     * )
     *
     * @var Collection<int, Document>
     */
    public $documents;

    public function __construct(string $name)
    {
        $this->name      = $name;
        $this->documents = new ArrayCollection();
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
