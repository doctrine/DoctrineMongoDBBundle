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

/**
 * Form type for a MongoDB document
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class DocumentType extends DoctrineType
{
    /**
     * Return the default loader object.
     *
     * @param ObjectManager $manager
     * @param array $options
     * @return MongoDBQueryBuilderLoader
     */
    protected function getLoader(ObjectManager $manager, array $options)
    {
        return new MongoDBQueryBuilderLoader(
            $options['query_builder'],
            $manager,
            $options['class']
        );
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = parent::getDefaultOptions($options);

        // alias "em" as "document_manager"
        $defaultOptions['document_manager'] = null;
        if (isset($options['document_manager'])) {
            if (isset($options['em'])) {
                throw new \InvalidArgumentException('You cannot set both an "em" and "document_manager" option.');
            }

            $defaultOptions['em'] = $options['document_manager'];
        }

        return $defaultOptions;
    }

    public function getName()
    {
        return 'document';
    }
}
