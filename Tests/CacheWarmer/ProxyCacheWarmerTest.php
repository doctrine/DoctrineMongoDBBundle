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

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\ProxyCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Proxy\ProxyFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProxyCacheWarmerTest extends \Doctrine\Bundle\MongoDBBundle\Tests\TestCase
{
    /** @var ContainerInterface */
    private $container;

    private $proxyMock;

    /** @var ProxyFactory */
    private $warmer;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.proxy_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_proxy_classes', Configuration::AUTOGENERATE_NEVER);

        $this->proxyMock = $this->getMockBuilder(ProxyFactory::class)->disableOriginalConstructor()->getMock();

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Validator']);
        $r = new \ReflectionObject($dm);
        $p = $r->getProperty('proxyFactory');
        $p->setAccessible(true);
        $p->setValue($dm, $this->proxyMock);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registryStub->expects($this->any())->method('getManagers')->willReturn([ $dm ]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new ProxyCacheWarmer($this->container);
    }

    public function testWarmerNotOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted()
    {
        $this->proxyMock->expects($this->once())->method('generateProxyClasses');
        $this->warmer->warmUp('meh');
    }

    /**
     * @dataProvider provideWarmerNotExecuted
     */
    public function testWarmerNotExecuted($autoGenerate)
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_proxy_classes', $autoGenerate);
        $this->proxyMock->expects($this->exactly(0))->method('generateProxyClasses');
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