<?php

namespace Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class TestDefaultRepoDocument
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    private $id;
}
