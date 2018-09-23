<?php

namespace Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomClassRepoRepository;

/**
 * @ODM\Document(repositoryClass=TestCustomClassRepoRepository::class)
 */
class TestCustomClassRepoDocument
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    private $id;
}
