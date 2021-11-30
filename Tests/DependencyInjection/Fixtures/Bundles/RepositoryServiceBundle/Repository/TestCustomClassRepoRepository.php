<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class TestCustomClassRepoRepository extends DocumentRepository
{
}
