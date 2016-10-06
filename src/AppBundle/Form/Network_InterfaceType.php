<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\RangeType;

class Network_InterfaceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
			->add('nom_virtuel','choice' , array(
                'choices' => array('tap' => 'Interface de type tap',
				'port' => 'Port d\'un OVS'),
				'empty_value' => '-- Choose a type --'
				))
			->add('nom_physique')
//            ->add('nbr_interface','integer')
            ->add('configreseau',new ConfigReseauType())
            ->add('save','submit')

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Network_Interface'
        ));
    }
}
