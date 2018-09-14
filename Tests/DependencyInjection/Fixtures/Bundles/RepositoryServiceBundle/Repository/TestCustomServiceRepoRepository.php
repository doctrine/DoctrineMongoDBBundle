<?php

namespace Fixtures\Bundles\RepositoryServiceBundle\Repository;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoDocument;

class TestCustomServiceRepoRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestCustomServiceRepoDocument::class);
    }
}
