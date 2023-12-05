<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\RepositoryServiceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\File */
#[ODM\File]
class TestDefaultRepoFile
{
    /** @ODM\Id */
    #[ODM\Id]
    private string $id;
}
