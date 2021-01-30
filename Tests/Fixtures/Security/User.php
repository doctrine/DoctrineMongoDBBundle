<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Security;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\User\UserInterface;

/** @ODM\Document */
class User implements UserInterface
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @param ObjectId $id
     * @param string   $name
     */
    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function getRoles(): void
    {
    }

    public function getPassword(): void
    {
    }

    public function getSalt(): void
    {
    }

    public function getUsername(): string
    {
        return $this->name;
    }

    public function eraseCredentials(): void
    {
    }

    public function equals(UserInterface $user): void
    {
    }
}
