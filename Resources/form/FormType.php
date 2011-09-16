<?php

namespace {{ namespace }}\Form\Type{{ entity_namespace ? '\\' ~ entity_namespace : '' }};

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class {{ form_class }} extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
        {%- for field in fields %}

            ->add('{{ field }}')

        {%- endfor %}

        ;
    }

    public function getDefaultOptions(array $options)
    {
        return array('data_class' => '{{ data_class }}');
    }

    
    public function getName()
    {
        return '{{ form_type_name }}';
    }
}
