<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

final class InfoDoctrineODMCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel      = new CommandTestKernel('test', false);
        $application = new Application($kernel);

        $command       = $application->find('doctrine:mongodb:mapping:info');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--dm' => 'command_test']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Found 1 documents mapped in document manager command_test', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    public function testExecuteWithDocumentManagerWithoutDocuments(): void
    {
        $kernel      = new CommandTestKernel('test', false);
        $application = new Application($kernel);

        $command       = $application->find('doctrine:mongodb:mapping:info');
        $commandTester = new CommandTester($command);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('You do not have any mapped Doctrine MongoDB ODM documents for any of your bundles. Create a class inside the Document namespace of any of your bundles and provide mapping information for it with Attributes directly in the classes doc blocks or with XML in your bundles Resources/config/doctrine/metadata/mongodb directory');

        $commandTester->execute(['--dm' => 'command_test_without_documents']);
    }
}
