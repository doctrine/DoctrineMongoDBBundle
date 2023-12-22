<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\APM;

use Doctrine\ODM\MongoDB\APM\CommandLoggerInterface;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use Symfony\Component\Stopwatch\Stopwatch;

use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;
use function sprintf;

final class StopwatchCommandLogger implements CommandLoggerInterface
{
    private bool $registered = false;

    public function __construct(private ?Stopwatch $stopwatch)
    {
    }

    public function register(): void
    {
        if ($this->stopwatch === null || $this->registered) {
            return;
        }

        $this->registered = true;
        addSubscriber($this);
    }

    public function unregister(): void
    {
        if (! $this->registered) {
            return;
        }

        removeSubscriber($this);
        $this->registered = false;
    }

    public function commandStarted(CommandStartedEvent $event): void
    {
        if (! $this->stopwatch) {
            return;
        }

        $this->stopwatch->start(sprintf('mongodb_%s', $event->getRequestId()), 'doctrine_mongodb');
    }

    public function commandSucceeded(CommandSucceededEvent $event): void
    {
        if (! $this->stopwatch) {
            return;
        }

        $this->stopwatch->stop(sprintf('mongodb_%s', $event->getRequestId()));
    }

    public function commandFailed(CommandFailedEvent $event): void
    {
        if (! $this->stopwatch) {
            return;
        }

        $this->stopwatch->stop(sprintf('mongodb_%s', $event->getRequestId()));
    }
}
