<?php


namespace Doctrine\Bundle\MongoDBBundle\Logger;

/**
 * An aggregate query logger.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class AggregateLogger implements LoggerInterface
{
    private $loggers;

    /**
     * Constructor.
     *
     * @param array $loggers An array of LoggerInterface objects
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function logQuery(array $query)
    {
        foreach ($this->loggers as $logger) {
            $logger->logQuery($query);
        }
    }
}
