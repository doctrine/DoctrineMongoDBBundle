<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Guesser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuesserTestType extends AbstractType
{
    /**
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('date')
            ->add('ts')
            ->add('categories', null, ['document_manager' => $options['dm']])
            ->add('boolField')
            ->add('booleanField')
            ->add('floatField')
            ->add('intField')
            ->add('integerField')
            ->add('collectionField')
            ->add('nonMappedField');
    }

    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Guesser::class,
        ])->setRequired('dm');
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'guesser_test';
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }
}
