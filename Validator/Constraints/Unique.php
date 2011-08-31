<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for the unique document validator
 *
 * @Annotation
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class Unique extends Constraint
{
    public $documentManager;
    public $message = 'This value is already used.';
    public $path;

    /**
     * @see Symfony\Component\Validator\Constraint::getDefaultOption()
     */
    public function getDefaultOption()
    {
        return 'path';
    }

    /**
     * @see Symfony\Component\Validator\Constraint::getRequiredOptions()
     */
    public function getRequiredOptions()
    {
        return array('path');
    }

    /**
     * @see Symfony\Component\Validator\Constraint::getTargets()
     */
    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }

    /**
     * @see Symfony\Component\Validator\Constraint::validatedBy()
     */
    public function validatedBy()
    {
        return 'doctrine_odm.mongodb.unique';
    }

    /**
     * Get the document manager ID to use for validating uniqueness.
     *
     * The default document manager will be returned by default if a document
     * manager ID has not been configured.
     *
     * @return string
     */
    public function getDocumentManagerId()
    {
        return null !== $this->documentManager
            ? sprintf('doctrine.odm.mongodb.%s_document_manager', $this->documentManager)
            : 'doctrine.odm.mongodb.document_manager';
    }
}
