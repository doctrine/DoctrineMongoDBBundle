<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document\TestUnmappedDocument;

/** @template-extends ServiceDocumentRepository<TestUnmappedDocument> */
class TestUnmappedDocumentRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestUnmappedDocument::class);
    }
}
