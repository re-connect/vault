<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserGestionnaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', null, ['required' => true, 'label' => 'name'])
            ->add('prenom', null, ['required' => true, 'label' => 'firstname'])
            ->add('email', EmailType::class, ['required' => true, 'label' => 'devenirUnRelaiReconnect.accueil.emailLbl']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['membre'],
        ]);
    }

    public function getName(): string
    {
        return 're_form_user_gestionnaire';
    }
}
