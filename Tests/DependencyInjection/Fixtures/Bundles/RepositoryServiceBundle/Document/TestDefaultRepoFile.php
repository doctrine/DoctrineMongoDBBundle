<?php

declare(strict_types=1);

namespace Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\File
 */
class TestDefaultRepoFile
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    private $id;
}
