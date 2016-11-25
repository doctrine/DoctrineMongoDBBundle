<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface as SymfonyLogger;
use Psr\Log\LoggerInterface as PsrLogger;

/**
 * A lightweight query logger.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class Logger implements LoggerInterface
{
    private $logger;
    private $prefix;
    private $batchInsertThreshold;

    public function __construct($logger = null, $prefix = 'MongoDB query: ')
    {
        if (!$logger instanceof PsrLogger && !$logger instanceof SymfonyLogger) {
            throw new \InvalidArgumentException(
                'Argument 1 of '.__METHOD__.' Must be an instance of Psr\Log\LoggerInterface or Symfony\Component\HttpKernel\Log\LoggerInterface.'
            );
        }

        $this->logger = $logger;
        $this->prefix = $prefix;
    }

    public function setBatchInsertThreshold($batchInsertThreshold)
    {
        $this->batchInsertThreshold = $batchInsertThreshold;
    }

    public function logQuery(array $query)
    {
        if (null === $this->logger) {
            return;
        }

        if (isset($query['batchInsert']) && null !== $this->batchInsertThreshold && $this->batchInsertThreshold <= $query['num']) {
            $query['data'] = '**'.$query['num'].' item(s)**';
        }

        array_walk_recursive($query, function(&$value, $key) {
            if ($value instanceof \MongoBinData) {
                $value = base64_encode($value->bin);
                return;
            }
            if (is_float($value) && is_infinite($value)) {
                $value = ($value < 0 ? '-' : '') . 'Infinity';
                return;
            }
            if (is_float($value) && is_nan($value)) {
                $value = 'NaN';
                return;
            }
        });

        $this->logger->debug($this->prefix.json_encode($query));
    }
}
