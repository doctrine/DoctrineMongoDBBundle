<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Doctrine\Bundle\DoctrineMongoDBBundle\Tests\Security;

use Doctrine\Bundle\DoctrineMongoDBBundle\Security\DocumentUserProvider;
use Doctrine\Bundle\DoctrineMongoDBBundle\Tests\TestCase;
use Doctrine\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Security\User;

class DocumentUserProviderTest extends TestCase
{
    const DOCUMENT_CLASS = 'Doctrine\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Security\User';

    private $documentManager;

    protected function setUp()
    {
        $this->documentManager = TestCase::createTestDocumentManager();
        $this->documentManager->createQueryBuilder(self::DOCUMENT_CLASS)
            ->remove()
            ->getQuery()
            ->execute();

        parent::setUp();
    }

    public function testRefreshUserGetsUserByIdentifier()
    {
        $user1 = new User(1, 'user1');
        $user2 = new User(2, 'user1');

        $this->documentManager->persist($user1);
        $this->documentManager->persist($user2);
        $this->documentManager->flush();

        $provider = new DocumentUserProvider($this->documentManager, self::DOCUMENT_CLASS);

        // try to change the user identity
        $user1->name = 'user2';

        $this->assertSame($user1, $provider->refreshUser($user1));
    }
}
