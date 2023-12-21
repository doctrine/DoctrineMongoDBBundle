<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Command\LoadDataFixturesDoctrineODMCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LoadDataFixturesDoctrineODMCommandTest extends KernelTestCase
{
    private LoadDataFixturesDoctrineODMCommand $command;

    protected function setUp(): void
    {
        $kernel      = new CommandTestKernel('test', false);
        $application = new Application($kernel);

        $this->command = $application->find('doctrine:mongodb:fixtures:load');
    }

    public function testIsInteractiveByDefault(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Careful, database will be purged. Do you want to continue (y/N) ?', $output);
    }

    public function testGroup(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            '--group' => ['test_group'],
        ], ['interactive' => false]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('loading Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures\UserFixtures', $output);
        $this->assertStringNotContainsString('loading Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures\OtherFixtures', $output);
    }

    public function testNonExistingGroup(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            '--group' => ['non_existing_group'],
        ], ['interactive' => false]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Could not find any fixture services to load in the groups', $output);
        $this->assertStringContainsString('(non_existing_group)', $output);
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([], ['interactive' => false]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('loading Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures\UserFixtures', $output);
        $this->assertStringContainsString('loading Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures\OtherFixtures', $output);
    }
}
