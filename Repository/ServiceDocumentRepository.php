<?php

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Optional DocumentRepository base class with a simplified constructor (for autowiring).
 *
 * To use in your class, inject the "registry" service and call
 * the parent constructor. For example:
 *
 * class YourDocumentRepository extends ServiceDocumentRepository
 * {
 *     public function __construct(RegistryInterface $registry)
 *     {
 *         parent::__construct($registry, YourDocument::class);
 *     }
 * }
 */
class ServiceDocumentRepository extends DocumentRepository implements ServiceDocumentRepositoryInterface
{
    /**
     * @param string $documentClass The class name of the entity this repository manages
     */
    public function __construct(ManagerRegistry $registry, $documentClass)
    {
        /** @var DocumentManager $manager */
        $manager = $registry->getManagerForClass($documentClass);

        parent::__construct($manager, $manager->getUnitOfWork(), $manager->getClassMetadata($documentClass));
    }
}
