<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search', null, ['label' => 'membre.userSearch.searchLabel', 'attr' => ['autocomplete' => 'off', 'placeholder' => 'membre.userSearch.searchPlaceholder']])
            ->add('id', HiddenType::class)
            ->add('rechercher', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }

    public function getName(): string
    {
        return 're_form_userSearch';
    }
}
