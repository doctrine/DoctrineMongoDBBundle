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

namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for the unique document validator
 *
 * @Annotation
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
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
}
