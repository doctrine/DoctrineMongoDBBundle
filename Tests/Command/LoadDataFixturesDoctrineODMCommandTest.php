<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Command\LoadDataFixturesDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoaderInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadDataFixturesDoctrineODMCommandTest extends TestCase
{
    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $kernel   = $this->createMock(KernelInterface::class);
        $loader   = $this->createMock(SymfonyFixturesLoaderInterface::class);

        $this->command = new LoadDataFixturesDoctrineODMCommand($registry, $kernel, $loader);
    }

    public function testCommandIsEnabledWithDependency(): void
    {
        $this->assertTrue($this->command->isEnabled());
    }
}
