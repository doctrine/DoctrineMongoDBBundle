<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;

/**
 * Optional GridFSRepository base class with a simplified constructor (for autowiring).
 *
 * To use in your class, inject the "registry" service and call
 * the parent constructor. For example:
 *
 * class YourDocumentRepository extends ServiceGridFSRepository
 * {
 *     public function __construct(RegistryInterface $registry)
 *     {
 *         parent::__construct($registry, YourDocument::class);
 *     }
 * }
 *
 * @template TDocument of object
 * @template-extends DefaultGridFSRepository<TDocument>
 */
class ServiceGridFSRepository extends DefaultGridFSRepository implements ServiceDocumentRepositoryInterface
{
    /** @use ServiceRepositoryTrait<TDocument> */
    use ServiceRepositoryTrait;
}
