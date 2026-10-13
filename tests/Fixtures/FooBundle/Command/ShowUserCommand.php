<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Command;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Document\User;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:show-user')]
final class ShowUserCommand
{
    public function __invoke(
        #[Argument]
        #[MapDocument]
        User $user,
        OutputInterface $output,
    ): int {
        $output->writeln($user->getId());

        return Command::SUCCESS;
    }
}
