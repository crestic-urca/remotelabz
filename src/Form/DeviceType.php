<?php

namespace App\Form;

use App\Entity\Device;
use App\Entity\Flavor;
use App\Entity\NetworkInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Entity\OperatingSystem;

class DeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Identifies this device'
                ]
            ])
            ->add('brand')
            ->add('model')
            ->add('launchOrder', IntegerType::class, [
                'attr' => [
                    'min' => 0
                ]
            ])
            ->add('operatingSystem', EntityType::class, [
                'class' => OperatingSystem::class,
                'choice_label' => 'name',
                'required' => false
            ])
            ->add('launchScript', FileType::class, [
                'required' => false
            ])
            ->add('networkInterfaces', EntityType::class, [
                'class' => NetworkInterface::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false
            ])
            ->add('flavor', EntityType::class, [
                'class' => Flavor::class,
                'choice_label' => 'name',
                'required' => false
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Device::class,
        ]);
    }
}
