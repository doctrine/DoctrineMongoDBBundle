<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/** @template-extends DocumentRepository<object> */
final class CustomGridFSRepository extends DocumentRepository
{
}
