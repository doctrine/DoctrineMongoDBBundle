<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;

trait ServiceRepositoryTrait
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
