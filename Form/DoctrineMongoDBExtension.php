<?php


namespace Doctrine\Bundle\MongoDBBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractExtension;

/**
 * Form extension.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DoctrineMongoDBExtension extends AbstractExtension
{
    protected $registry = null;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return [
            new Type\DocumentType($this->registry),
        ];
    }

    protected function loadTypeGuesser()
    {
        return new DoctrineMongoDBTypeGuesser($this->registry);
    }
}
