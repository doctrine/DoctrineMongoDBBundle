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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'document_manager' => null,
            'em' => function (Options $options, $previousValue) {
                if ($options->get('document_manager') && $previousValue) {
                    throw new \InvalidArgumentException('You cannot set both an "em" and "document_manager" option.');
                }

                return $options->get('document_manager');
            },
        ));
    }

    /**
     * @see Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'document';
    }
}
