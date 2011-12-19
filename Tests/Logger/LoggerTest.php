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

namespace Doctrine\Bundle\MongoDBBundle\Tests\Logger;

use Doctrine\Bundle\MongoDBBundle\Logger\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    private $logger;

    protected function setUp()
    {
        $this->logger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface');
    }

    protected function tearDown()
    {
        unset($this->logger);
    }

    public function testLogQuery()
    {
        $query = array('foo' => 'bar');
        $log = json_encode($query);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('MongoDB query: '.$log);

        $logger = new Logger($this->logger);
        $logger->logQuery(array('foo' => 'bar'));
    }
}
