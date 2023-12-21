<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Form\ChoiceList;

use Closure;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use function array_values;

/**
 * Getting Entities through the MongoDB QueryBuilder
 */
class MongoDBQueryBuilderLoader implements EntityLoaderInterface
{
    /**
     * Contains the query builder that builds the query for fetching the
     * entities
     *
     * This property should only be accessed through queryBuilder.
     */
    private Builder $queryBuilder;

    /**
     * Construct an ORM Query Builder Loader
     */
    public function __construct(Builder|Closure $queryBuilder, ?ObjectManager $manager = null, ?string $class = null)
    {
        if ($queryBuilder instanceof Closure) {
            $queryBuilder = $queryBuilder($manager->getRepository($class));

            if (! $queryBuilder instanceof Builder) {
                throw new UnexpectedTypeException($queryBuilder, Builder::class);
            }
        }

        $this->queryBuilder = $queryBuilder;
    }

    /** @return object[] */
    public function getEntities(): array
    {
        return array_values($this->queryBuilder->getQuery()->execute()->toArray());
    }

    /** @return object[] */
    public function getEntitiesByIds(string $identifier, array $values): array
    {
        $qb = clone $this->queryBuilder;

        return array_values($qb
            ->field($identifier)->in($values)
            ->getQuery()
            ->execute()
            ->toArray());
    }
}
