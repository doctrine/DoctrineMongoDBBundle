<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command\Encryption;

use Doctrine\Bundle\MongoDBBundle\Tests\Command\CommandTestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DiagnosticCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel      = new CommandTestKernel('test', false);
        $application = new Application($kernel);

        $command       = $application->find('doctrine:mongodb:encryption:diagnostic');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('MongoDB extension loaded', $output);
        $this->assertStringContainsString('mongocryptd', $output);
        $this->assertStringContainsString('Connection: default', $output);
        $this->assertStringContainsString('Auto encryption is not enabled for this connection.', $output);
    }
}
