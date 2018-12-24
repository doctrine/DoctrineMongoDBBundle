<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use LogicException;
use function sprintf;

trait ServiceRepositoryTrait
{
    /**
     * @param string $documentClass The class name of the entity this repository manages
     */
    public function __construct(ManagerRegistry $registry, $documentClass)
    {
        /** @var DocumentManager $manager */
        $manager = $registry->getManagerForClass($documentClass);

        if ($manager === null) {
            throw new LogicException(sprintf(
                'Could not find the document manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this document’s metadata.',
                $documentClass
            ));
        }

        parent::__construct($manager, $manager->getUnitOfWork(), $manager->getClassMetadata($documentClass));
    }
}
