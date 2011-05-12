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
use Doctrine\ODM\MongoDB\DocumentManager;

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
    protected $documentManager = null;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    protected function loadTypes()
    {
        return array(
            new Type\DocumentType($this->documentManager),
        );
    }
}
