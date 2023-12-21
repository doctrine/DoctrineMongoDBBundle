<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository\TestCustomServiceRepoGridFSRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\File(repositoryClass: TestCustomServiceRepoGridFSRepository::class)]
class TestCustomServiceRepoFile
{
    #[ODM\Id]
    private string $id;
}
