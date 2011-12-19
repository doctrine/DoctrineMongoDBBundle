<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Tests\Security;

use Doctrine\Bundle\MongoDBBundle\Security\DocumentUserProvider;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Security\User;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;

class DocumentUserProviderTest extends TestCase
{
    const DOCUMENT_CLASS = 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Security\User';

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
