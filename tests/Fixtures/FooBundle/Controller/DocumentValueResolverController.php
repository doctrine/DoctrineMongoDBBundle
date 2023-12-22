<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Controller;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Document\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class DocumentValueResolverController
{
    #[Route(path: '/user/{id}', name: 'tv_user_show')]
    public function showUserByDefault(
        User $user,
    ): Response {
        return new Response($user->getId());
    }

    #[Route(path: '/user_with_identifier/{identifier}', name: 'tv_user_show_with_identifier')]
    public function showUserWithMapping(
        #[MapDocument(mapping: ['identifier' => 'id'])]
        User $user,
    ): Response {
        return new Response($user->getId());
    }
}
