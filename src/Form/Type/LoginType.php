<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setAction($options['router']->generate('re_main_login'));

        $builder
            ->add('_username', null, ['label' => 'connexionForm.usernameV2'])
            ->add('_password', PasswordType::class, ['label' => 'connexionForm.motDePasse'])
            ->add('_remember_me', HiddenType::class, ['data' => true])
            ->add('_csrf_token', HiddenType::class, ['data' => $options['csrfToken']])
            ->add('_submit', SubmitType::class, ['label' => 'connexionForm.connexion']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'router' => null,
            'csrfToken' => null,
        ]);
    }

    public function getName(): ?string
    {
        return null;
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
