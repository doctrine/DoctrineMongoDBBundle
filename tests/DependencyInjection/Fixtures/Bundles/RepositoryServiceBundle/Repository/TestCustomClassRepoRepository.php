<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/** @template-extends DocumentRepository<object> */
class TestCustomClassRepoRepository extends DocumentRepository
{
}
