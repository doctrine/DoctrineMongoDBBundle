<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\Command\DoctrineODMCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineODMCommandTest extends KernelTestCase
{
    /** @dataProvider provideDmName */
    public function testSetApplicationManager(?string $dmName): void
    {
        $kernel = new CommandTestKernel('test', false);
        $kernel->boot();
        $application = new Application($kernel);

        DoctrineODMCommand::setApplicationDocumentManager($application, $dmName);

        $this->assertInstanceOf(DocumentManagerHelper::class, $application->getHelperSet()->get('dm'));
    }

    public static function provideDmName(): iterable
    {
        yield ['command_test'];
        yield [null];
    }
}
