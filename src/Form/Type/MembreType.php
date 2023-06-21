<?php

namespace App\Form\Type;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MembreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', UserType::class)
            ->add('membresCentres', MembreCentreType::class, ['centres' => $options['centres'], 'mapped' => false])
            ->add('submit', SubmitType::class, ['label' => 'confirm']);

        // Remove the submit button of user form
        $userForm = $builder->get('user');
        $userForm->remove('submit');
        $userForm->remove('adresse');
        if ($options['removeUsername']) {
            $userForm->remove('username');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'validation_groups' => ['membre', 'password', 'password-membre'],
            'centres' => null,
            'removeUsername' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_membre';
    }
}
