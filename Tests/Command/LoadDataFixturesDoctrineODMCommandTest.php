<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Command\LoadDataFixturesDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoaderInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\DataFixtures\Loader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Henrik Bjornskov <henrik@bjrnskov.dk>
 */
class LoadDataFixturesDoctrineODMCommandTest extends TestCase
{
    public function setUp()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $kernel = $this->createMock(KernelInterface::class);
        $loader = $this->createMock(SymfonyFixturesLoaderInterface::class);

        $this->command = new LoadDataFixturesDoctrineODMCommand($registry, $kernel, $loader);
    }

    public function testCommandIsNotEnabledWithMissingDependency()
    {
        if (class_exists(Loader::class)) {
            $this->markTestSkipped();
        }

        $this->assertFalse($this->command->isEnabled());
    }

    public function testCommandIsEnabledWithDependency()
    {
        if (!class_exists(Loader::class)) {
            $this->markTestSkipped();
        }

        $this->assertTrue($this->command->isEnabled());
    }
}
