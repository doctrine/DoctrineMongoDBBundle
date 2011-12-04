<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Logger;

use Symfony\Bundle\DoctrineMongoDBBundle\Logger\StandardLogger;

class StandardLoggerTest extends \PHPUnit_Framework_TestCase
{
    private $innerLogger;
    private $logger;

    protected function setUp()
    {
        $this->innerLogger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface');
        $this->logger = new StandardLogger($this->innerLogger);
    }

    protected function tearDown()
    {
        unset(
            $this->innerLogger,
            $this->logger
        );
    }

    public function testLogQuery()
    {
        $query = array('foo' => 'bar');
        $log = json_encode($query);

        $this->innerLogger->expects($this->once())
            ->method('info')
            ->with('MongoDB query: '.$log);

        $this->logger->logQuery(array('foo' => 'bar'));

        $this->assertEquals(1, $this->logger->getNbQueries());
        $this->assertEquals(array($log), $this->logger->getQueries());
    }
}
