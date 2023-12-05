<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
#[ODM\Document]
class TestDefaultRepoDocument
{
    /** @ODM\Id */
    #[ODM\Id]
    private string $id;
}
