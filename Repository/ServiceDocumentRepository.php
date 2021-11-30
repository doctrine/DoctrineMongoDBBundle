<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Optional DocumentRepository base class with a simplified constructor (for autowiring).
 *
 * To use in your class, inject the "registry" service and call
 * the parent constructor. For example:
 *
 * namespace AppBundle\Repository;
 *
 * use AppBundle\Document\YourDocument;
 * use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
 * use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
 *
 * class YourDocumentRepository extends ServiceDocumentRepository
 * {
 *     public function __construct(ManagerRegistry $registry)
 *     {
 *         parent::__construct($registry, YourDocument::class);
 *     }
 * }
 *
 * @template T of object
 * @template-extends DocumentRepository<T>
 */
class ServiceDocumentRepository extends DocumentRepository implements ServiceDocumentRepositoryInterface
{
    /** @use ServiceRepositoryTrait<T> */
    use ServiceRepositoryTrait;
}
