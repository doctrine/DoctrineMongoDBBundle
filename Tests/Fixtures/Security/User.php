<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Security;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Security\Core\User\UserInterface;

/** @ODM\Document */
class User implements UserInterface
{
    /** @ODM\Id(strategy="none") */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function getRoles()
    {
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
        return $this->name;
    }

    public function eraseCredentials()
    {
    }

    public function equals(UserInterface $user)
    {
    }
}
