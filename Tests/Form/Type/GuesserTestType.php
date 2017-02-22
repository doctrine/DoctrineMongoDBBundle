<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;


use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Guesser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Vladimir Chub <v@chub.com.ua>
 */
class GuesserTestType extends AbstractType
{
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Guesser::class
        ])->setRequired('dm');
    }

    public function getBlockPrefix()
    {
        return 'guesser_test';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
