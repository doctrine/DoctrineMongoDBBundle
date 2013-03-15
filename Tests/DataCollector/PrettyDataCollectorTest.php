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

namespace Doctrine\Bundle\MongoDBBundle\Tests\DataCollector;

use Doctrine\Bundle\MongoDBBundle\DataCollector\PrettyDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrettyDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getQueries
     */
    public function testCollect($query, $formatted)
    {
        $collector = new PrettyDataCollector();
        $collector->logQuery($query);
        $collector->collect(new Request(), new Response());

        $this->assertEquals(1, $collector->getQueryCount());
        $this->assertEquals($formatted, $collector->getQueries());
    }

    public function getQueries()
    {
        return array(
            'batch insert' => array(
                array('db' => 'foo', 'collection' => 'bar', 'batchInsert' => true, 'num' => 1, 'data' => array('foo' => 'bar'), 'options' => array()),
                array('use foo;', 'db.bar.insert({ "foo": "bar" });'),
            ),
            'find' => array(
                array('db' => 'foo', 'collection' => 'bar', 'find' => true, 'query' => array('foo' => null), 'fields' => array()),
                array('use foo;', 'db.bar.find({ "foo": null });'),
            ),
            'bin data' => array(
                array('db' => 'foo', 'collection' => 'bar', 'update' => true, 'query' => array('_id' => 'foo'), 'newObj' => array('foo' => new \MongoBinData('junk data', \MongoBinData::BYTE_ARRAY))),
                arraY('use foo;', 'db.bar.update({ "_id": "foo" }, { "foo": new BinData(2, "' . base64_encode('junk data') . '") });'),
            )
        );
    }
}
