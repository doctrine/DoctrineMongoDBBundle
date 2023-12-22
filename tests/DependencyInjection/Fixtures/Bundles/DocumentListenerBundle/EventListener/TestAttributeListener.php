<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\DocumentListenerBundle\EventListener;

use Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener;

#[AsDocumentListener(event: 'prePersist', connection: 'test', priority: 10)]
class TestAttributeListener
{
    public function prePersist(): void
    {
    }
}
