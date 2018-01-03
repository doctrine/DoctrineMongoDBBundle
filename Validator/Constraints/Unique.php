<?php


namespace Doctrine\Bundle\MongoDBBundle\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Constraint for the unique document validator
 *
 * @Annotation
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class Unique extends UniqueEntity
{
    public $service = 'doctrine_odm.mongodb.unique';
}
