<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;

/** @deprecated since 4.7.0, use the {@see \Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener} attribute instead */
interface EventSubscriberInterface extends EventSubscriber
{
}
