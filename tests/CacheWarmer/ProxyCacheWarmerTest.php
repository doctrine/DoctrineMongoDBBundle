<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\ProxyCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Proxy\Factory\ProxyFactory;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function sys_get_temp_dir;

class ProxyCacheWarmerTest extends TestCase
{
    private ContainerInterface $container;

    /** @var ProxyFactory&MockObject */
    private ProxyFactory $proxyMock;

    private ProxyCacheWarmer $warmer;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->setParameter('doctrine_mongodb.odm.proxy_dir', sys_get_temp_dir());
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_proxy_classes', Configuration::AUTOGENERATE_EVAL);

        $this->proxyMock = $this->getMockBuilder(ProxyFactory::class)->disableOriginalConstructor()->getMock();

        $dm = $this->createTestDocumentManager([__DIR__ . '/../Fixtures/Validator']);
        $r  = new ReflectionObject($dm);
        $p  = $r->getProperty('proxyFactory');
        $p->setAccessible(true);
        $p->setValue($dm, $this->proxyMock);

        $registryStub = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registryStub->method('getManagers')->willReturn([$dm]);
        $this->container->set('doctrine_mongodb', $registryStub);

        $this->warmer = new ProxyCacheWarmer($this->container);
    }

    public function testWarmerNotOptional(): void
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmerExecuted(): void
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_proxy_classes', Configuration::AUTOGENERATE_FILE_NOT_EXISTS);

        $this->proxyMock
            ->expects($this->once())
            ->method('generateProxyClasses')
            ->with($this->countOf(1));
        $this->warmer->warmUp('meh');
    }

    public function testWarmerNotExecuted(): void
    {
        $this->container->setParameter('doctrine_mongodb.odm.auto_generate_proxy_classes', Configuration::AUTOGENERATE_EVAL);
        $this->proxyMock->expects($this->exactly(0))->method('generateProxyClasses');
        $this->warmer->warmUp('meh');
    }
}
