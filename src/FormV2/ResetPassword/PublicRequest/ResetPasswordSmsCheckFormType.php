<?php

namespace App\FormV2\ResetPassword\PublicRequest;

use Symfony\Component\Form\AbstractType;
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
                'label' => 'resetting.public.votreTelephone',
                'label_attr' => ['class' => 'mb-2'],
                'attr' => [
                    'class' => 'w-50 mx-auto mb-2',
                    'readonly' => true,
                ],
            ])
            ->add('smsCode', null, [
                'required' => true,
                'label' => 'public_reset_password_SMS_your_code',
                'label_attr' => ['class' => 'mb-2'],
                'attr' => ['class' => 'w-50 mx-auto text-primary mb-2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordCheckSMSFormModel::class,
        ]);
    }
}
