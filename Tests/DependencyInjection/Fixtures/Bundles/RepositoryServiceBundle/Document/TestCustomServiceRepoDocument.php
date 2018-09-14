<?php

namespace Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoRepository;

/**
 * @ODM\Document(repositoryClass=TestCustomServiceRepoRepository::class)
 */
class TestCustomServiceRepoDocument
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    private $id;
}
