<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface as SymfonyLogger;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface as SymfonyDebugLogger;

/**
 * A lightweight query logger.
 * 
 * @author Kris Wallsmith <kris@symfony.com>
 */
class StandardLogger implements LoggerInterface
{
    private $logger;
    private $prefix;
    private $nbQueries;

    public function __construct(SymfonyLogger $logger = null, $prefix = 'MongoDB query: ')
    {
        $this->logger = $logger;
        $this->prefix = $prefix;
        $this->nbQueries = 0;
    }

    public function logQuery(array $query)
    {
        ++$this->nbQueries;

        if (null !== $this->logger) {
            if (isset($query['batchInsert'])) {
                $this->logger->info($this->prefix.json_encode(array('data' => '[omitted]') + $query));
            } else {
                $this->logger->info($this->prefix.json_encode($query));
            }
        }
    }

    public function getNbQueries()
    {
        return $this->nbQueries;
    }

    public function getQueries()
    {
        $queries = array();

        if ($this->logger && $this->logger instanceof SymfonyDebugLogger) {
            foreach ($this->logger->getLogs() as $log) {
                if (0 === strpos($log['message'], $this->prefix)) {
                    $queries[] = $log['message'];
                }
            }
        }

        return $queries;
    }
}
