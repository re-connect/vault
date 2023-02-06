<?php

namespace App\FormV2;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'main.chercher',
                    'data-action' => 'input->ajax-search#search',
                    'class' => 'ajax-search-input border-0 rounded-pill p-0',
                    'autofocus' => true,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
