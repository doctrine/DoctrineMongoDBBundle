<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Security;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\User\UserInterface;

#[ODM\Document]
class User implements UserInterface
{
    #[ODM\Id(strategy: 'none')]
    protected ObjectId $id;

    #[ODM\Field(type: Type::STRING)]
    public string $name;

    public function __construct(ObjectId $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): void
    {
    }

    public function getSalt(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->name;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function eraseCredentials(): void
    {
    }

    public function equals(UserInterface $user): void
    {
    }
}
