<?php

declare(strict_types=1);

namespace Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoGridFSRepository;

/**
 * @ODM\File(repositoryClass=TestCustomServiceRepoGridFSRepository::class)
 */
class TestCustomServiceRepoFile
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    private $id;
}
