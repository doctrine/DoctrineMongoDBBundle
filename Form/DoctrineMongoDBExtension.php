<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Form extension.
 */
class DoctrineMongoDBExtension extends AbstractExtension
{
    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return FormTypeInterface[]
     */
    protected function loadTypes()
    {
        return [
            new Type\DocumentType($this->registry),
        ];
    }

    /**
     * @return FormTypeGuesserInterface|null
     */
    protected function loadTypeGuesser()
    {
        return new DoctrineMongoDBTypeGuesser($this->registry);
    }
}
