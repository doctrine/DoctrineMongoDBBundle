<?php

declare(strict_types=1);

namespace Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoDocumentRepository;

/**
 * @ODM\Document(repositoryClass=TestCustomServiceRepoDocumentRepository::class)
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
