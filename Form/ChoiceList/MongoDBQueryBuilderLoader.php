<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Form\ChoiceList;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

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
     *
     * @var Builder
     */
    private $queryBuilder;

    /**
     * Construct an ORM Query Builder Loader
     *
     * @param Builder|\Closure $queryBuilder
     * @param ObjectManager $manager
     * @param string $class
     */
    public function __construct($queryBuilder, ObjectManager $manager = null, $class = null)
    {
        // If a query builder was passed, it must be a closure or QueryBuilder
        // instance
        if (!($queryBuilder instanceof Builder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, Builder::class .'  or ' . \Closure::class);
        }

        if ($queryBuilder instanceof \Closure) {
            $queryBuilder = $queryBuilder($manager->getRepository($class));

            if (!$queryBuilder instanceof Builder) {
                throw new UnexpectedTypeException($queryBuilder, Builder::class);
            }
        }

        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntities()
    {
        return array_values($this->queryBuilder->getQuery()->execute()->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function getEntitiesByIds($identifier, array $values)
    {
        $qb = clone ($this->queryBuilder);

        return array_values($qb
            ->field($identifier)->in($values)
            ->getQuery()
            ->execute()
            ->toArray()
        );
    }
}
