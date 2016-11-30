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

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\HydratorCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HydratorCacheWarmerTest extends \Doctrine\Bundle\MongoDBBundle\Tests\TestCase
{
    /** @var ContainerInterface */
    private $container;

    private $hydratorMock;

    /** @var HydratorCacheWarmer */
    private $warmer;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.hydrator_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes', Configuration::AUTOGENERATE_NEVER);

        $this->hydratorMock = $this->getMockBuilder(HydratorFactory::class)->disableOriginalConstructor()->getMock();

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Validator']);
        $r = new \ReflectionObject($dm);
        $p = $r->getProperty('hydratorFactory');
        $p->setAccessible(true);
        $p->setValue($dm, $this->hydratorMock);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registryStub->expects($this->any())->method('getManagers')->willReturn([ $dm ]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new HydratorCacheWarmer($this->container);
    }

    public function testWarmerNotOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted()
    {
        $this->hydratorMock->expects($this->once())->method('generateHydratorClasses');
        $this->warmer->warmUp('meh');
    }

    /**
     * @dataProvider provideWarmerNotExecuted
     */
    public function testWarmerNotExecuted($autoGenerate)
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_hydrator_classes', $autoGenerate);
        $this->hydratorMock->expects($this->exactly(0))->method('generateHydratorClasses');
        $this->warmer->warmUp('meh');
    }

    public function provideWarmerNotExecuted()
    {
        return [
            [ Configuration::AUTOGENERATE_ALWAYS ],
            [ Configuration::AUTOGENERATE_EVAL ],
            [ Configuration::AUTOGENERATE_FILE_NOT_EXISTS ],
        ];
    }
}