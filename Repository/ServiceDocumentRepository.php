<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMongoDBBundle\DoctrineMongoDBBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
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
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
class ServiceDocumentRepository extends DocumentRepository implements ServiceDocumentRepositoryInterface
{
    /**
     * @param ManagerRegistry $registry
     * @param string          $documentClass The class name of the document this repository manages
     */
    public function __construct(ManagerRegistry $registry, $documentClass)
    {
        $manager = $registry->getManagerForClass($documentClass);

        parent::__construct($manager, $manager->getClassMetadata($documentClass));
    }
}
