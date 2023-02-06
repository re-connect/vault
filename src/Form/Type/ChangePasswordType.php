<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('currentPassword', PasswordType::class, ['label' => 'user.parametres.mdpActuel', 'mapped' => false, 'attr' => ['class' => 'border-blue-secondary']])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'user.parametres.nouveauMotDePasse'],
                'second_options' => ['label' => 'user.parametres.nouveauMotDePasseConfirm'],
                'invalid_message' => 'fos_user.password.mismatch',
                'attr' => ['class' => 'border-blue-secondary'],
            ])
            ->add('submit', SubmitType::class, ['label' => 'confirm']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['password'],
        ]);
    }

    public function getName(): string
    {
        return 're_form_changePassword';
    }
}
