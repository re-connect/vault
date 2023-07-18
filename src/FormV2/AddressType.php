<?php

namespace App\FormV2;

use App\Entity\Adresse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'street',
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'city',
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'postal_code',
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('pays', TextType::class, [
                'label' => 'country',
                'row_attr' => ['class' => 'col-6 mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adresse::class,
        ]);
    }
}
