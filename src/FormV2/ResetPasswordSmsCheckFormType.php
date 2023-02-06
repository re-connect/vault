<?php

namespace App\FormV2;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordSmsCheckFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phone', TelType::class, [
                'required' => true,
                'data' => $options['phone'],
                'label' => 'resetting.public.votreTelephone',
                'label_attr' => ['class' => 'text-primary mb-2'],
                'attr' => [
                    'class' => 'w-50 mx-auto text-primary mb-2',
                    'readonly' => true,
                ],
            ])
            ->add('smsCode', null, [
                'required' => true,
                'label' => 'public_reset_password_SMS_your_code',
                'label_attr' => ['class' => 'text-primary mb-2'],
                'attr' => ['class' => 'w-50 mx-auto text-primary mb-2'],
            ])
            ->add('submitFormReset', SubmitType::class, [
                'label' => 'confirm',
                'attr' => ['class' => 'btn btn-primary mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'phone' => null,
        ]);
    }
}
