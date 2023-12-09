<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoDocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(repositoryClass: TestCustomServiceRepoDocumentRepository::class)]
class TestCustomServiceRepoDocument
{
    #[ODM\Id]
    private string $id;
}
