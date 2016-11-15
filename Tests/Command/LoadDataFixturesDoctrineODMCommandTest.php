<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Command\LoadDataFixturesDoctrineODMCommand;
use Doctrine\Common\DataFixtures\Loader;

/**
 * @author Henrik Bjornskov <henrik@bjrnskov.dk>
 */
class LoadDataFixturesDoctrineODMCommandTest extends \PHPUnit_Framework_TestCase
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
        if (!class_exists(Loader::class)) {
            $this->markTestSkipped();
        }

        $this->assertTrue($this->command->isEnabled());
    }
}
