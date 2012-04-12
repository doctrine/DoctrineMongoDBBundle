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

namespace Doctrine\Bundle\MongoDBBundle\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\ChoiceList\MongoDBQueryBuilderLoader;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\DoctrineType;
use Symfony\Component\Form\Options;

/**
 * Form type for a MongoDB document
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class DocumentType extends DoctrineType
{
    /**
     * @see Symfony\Bridge\Doctrine\Form\Type\DoctrineType::getLoader()
     */
    public function getLoader(ObjectManager $manager, $queryBuilder, $class)
    {
        return new MongoDBQueryBuilderLoader(
            $queryBuilder,
            $manager,
            $class
        );
    }

    /**
     * @see Symfony\Bridge\Doctrine\Form\Type\DoctrineType::getDefaultOptions()
     */
    public function getDefaultOptions()
    {
        $defaultOptions = parent::getDefaultOptions();

        // alias "em" as "document_manager"
        $defaultOptions['document_manager'] = null;
        $defaultOptions['em'] = function (Options $options) {
            if (isset($options['document_manager'])) {
                if (isset($options['em'])) {
                    throw new \InvalidArgumentException('You cannot set both an "em" and "document_manager" option.');
                }

                return $options['document_manager'];
            }

            return null;
        };

        return $defaultOptions;
    }

    /**
     * @see Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'document';
    }
}
