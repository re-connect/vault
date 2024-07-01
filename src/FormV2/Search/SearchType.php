<?php

namespace App\FormV2\Search;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'search',
                    'class' => 'border-0 rounded-pill p-0',
                    'autofocus' => true,
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SearchFormModel::class]);
    }
}
