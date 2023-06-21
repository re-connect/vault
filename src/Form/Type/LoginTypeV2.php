<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginTypeV2 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_username', null, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'form_login',
                    'class' => 'mb-2 py-1 text-primary lighter fw-bold',
                ],
            ])
            ->add('_password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'password',
                    'class' => 'mb-3 py-1 text-primary lighter fw-bold login-form-password-input',
                ],
            ])
            ->add('_remember_me', HiddenType::class, [
                'data' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
