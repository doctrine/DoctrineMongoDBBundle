<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Command\LoadDataFixturesDoctrineODMCommand;
use Doctrine\Common\DataFixtures\Loader;
use PHPUnit\Framework\TestCase;
use function class_exists;

class LoadDataFixturesDoctrineODMCommandTest extends TestCase
{
    public function setUp()
    {
        $this->command = new LoadDataFixturesDoctrineODMCommand();
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
        if (! class_exists(Loader::class)) {
            $this->markTestSkipped();
        }

        $this->assertTrue($this->command->isEnabled());
    }
}
