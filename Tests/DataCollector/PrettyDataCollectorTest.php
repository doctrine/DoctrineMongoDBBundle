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
        return [
            'batch insert' => [
                ['db' => 'foo', 'collection' => 'bar', 'batchInsert' => true, 'num' => 1, 'data' => ['foo' => 'bar'], 'options' => []],
                ['use foo;', 'db.bar.insert({ "foo": "bar" });'],
            ],
            'find' => [
                ['db' => 'foo', 'collection' => 'bar', 'find' => true, 'query' => ['foo' => null], 'fields' => []],
                ['use foo;', 'db.bar.find({ "foo": null });'],
            ],
            'bin data' => [
                ['db' => 'foo', 'collection' => 'bar', 'update' => true, 'query' => ['_id' => 'foo'], 'newObj' => ['foo' => new \MongoBinData('junk data', \MongoBinData::BYTE_ARRAY)]],
                ['use foo;', 'db.bar.update({ "_id": "foo" }, { "foo": new BinData(2, "' . base64_encode('junk data') . '") });'],
            ]
        ];
    }

    public function testCollectLimit()
    {
        $queries = [
            [
                'find' => true,
                'query' => [
                    'path' => '/',
                ],
                'fields' => [],
                'db' => 'foo',
                'collection' => 'Route',
            ],
            [
                'find' => true,
                'query' => ['_id' => 'foo'],
                'fields' => [],
                'db' => 'foo',
                'collection' => 'User',
            ],
            [
                'limit' => true,
                'limitNum' => 1,
                'query' => ['_id' => 'foo'],
                'fields' => [],
            ],
            [
                'limit' => true,
                'limitNum' => NULL,
                'query' => ['_id' => 'foo'],
                'fields' => [],
            ],
            [
                'find' => true,
                'query' => [
                    '_id' => '5506fa1580c7e1ee3c8b4c60',
                ],
                'fields' => [],
                'db' => 'foo',
                'collection' => 'Group',
            ],
            [
                'limit' => true,
                'limitNum' => 1,
                'query' => [
                    '_id' => '5506fa1580c7e1ee3c8b4c60',
                ],
                'fields' => [],
            ],
            [
                'limit' => true,
                'limitNum' => NULL,
                'query' => [
                    '_id' => '5506fa1580c7e1ee3c8b4c60',
                ],
                'fields' => [],
            ],
        ];
        $formatted = [
            'use foo;',
            'db.Route.find({ "path": "/" });',
            'db.User.find({ "_id": "foo" }).limit(1);',
            'db.Group.find({ "_id": "5506fa1580c7e1ee3c8b4c60" }).limit(1);'
        ];

        $collector = new PrettyDataCollector();
        foreach ($queries as $query) {
            $collector->logQuery($query);
        }
        $collector->collect(new Request(), new Response());

        $this->assertEquals(3, $collector->getQueryCount());
        $this->assertEquals($formatted, $collector->getQueries());
    }
}
