<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Form;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Bundle\DoctrineMongoDBBundle\RegistryInterface;

/**
 * Form extension.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DoctrineMongoDBExtension extends AbstractExtension
{
    /**
     * The Doctrine 2 document manager
     * @var DocumentManager
     */
    protected $registry = null;

    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return array(
            new Type\DocumentType($this->registry),
        );
    }

    protected function loadTypeGuesser()
    {
        return new DoctrineMongoDBTypeGuesser($this->registry);
    }
}
