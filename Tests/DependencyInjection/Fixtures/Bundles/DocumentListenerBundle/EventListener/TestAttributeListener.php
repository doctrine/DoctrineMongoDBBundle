<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\DocumentListenerBundle\EventListener;

use Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener;

#[AsDocumentListener(event: 'prePersist', method: 'onPrePersist', lazy: true, connection: 'test', priority: 10)]
class TestAttributeListener
{
    public function onPrePersist(): void
    {
    }
}
