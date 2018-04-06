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

namespace Fixtures\Bundles\RepositoryServiceBundle\Repository;

use DoctrineMongoDBBundle\DoctrineMongoDBBundle\Repository\ServiceDocumentRepository;
use Fixtures\Bundles\RepositoryServiceBundle\Document\TestCustomServiceRepoDocument;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TestCustomServiceRepoRepository extends ServiceDocumentRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TestCustomServiceRepoDocument::class);
    }
}
