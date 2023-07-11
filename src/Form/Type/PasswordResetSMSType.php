<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Form\Entity\PasswordResetSMS;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordResetSMSType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('smsCode', TextType::class, [
                'label' => 'user.reinitialiserMdp.smsCodeLabel',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'user.parametres.nouveauMotDePasse'],
                'second_options' => ['label' => 'user.parametres.nouveauMotDePasseConfirm'],
                'invalid_message' => 'fos_user.password.mismatch',
                'attr' => ['class' => 'border-blue-secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => PasswordResetSMS::class,
                'validation_groups' => ['password'],
            ])
            ->setRequired(['user'])
            ->setAllowedTypes('user', User::class);
    }
}
