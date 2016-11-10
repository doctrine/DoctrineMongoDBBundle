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
