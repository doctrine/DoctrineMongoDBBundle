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

/**
 * A lightweight query logger.
 * 
 * @author Kris Wallsmith <kris@symfony.com>
 */
class Logger implements LoggerInterface
{
    private $logger;
    private $prefix;
    private $batchInsertTreshold;

    public function __construct(SymfonyLogger $logger = null, $prefix = 'MongoDB query: ')
    {
        $this->logger = $logger;
        $this->prefix = $prefix;
    }

    public function setBatchInsertThreshold($batchInsertTreshold)
    {
        $this->batchInsertTreshold = $batchInsertTreshold;
    }

    public function logQuery(array $query)
    {
        if (null === $this->logger) {
            return;
        }

        if (isset($query['batchInsert']) && null !== $this->batchInsertTreshold && $this->batchInsertTreshold <= $query['num']) {
            $query['data'] = '**'.$query['num'].' item(s)**';
        }

        $this->logger->info($this->prefix.json_encode($query));
    }
}
