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
    public function __construct(private ManagerRegistry $registry)
    {
    }

    /** @return FormTypeInterface[] */
    protected function loadTypes(): array
    {
        return [
            new Type\DocumentType($this->registry),
        ];
    }

    protected function loadTypeGuesser(): ?FormTypeGuesserInterface
    {
        return new DoctrineMongoDBTypeGuesser($this->registry);
    }
}
