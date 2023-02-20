<?php

namespace App\FormV2;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'name',
                'attr' => [
                    'data-action' => 'click->autocomplete#autocomplete',
                ],
            ]);

        if (!$options['rename_only']) {
            $builder
                ->add('bPrive', ChoiceType::class, [
                    'label' => 'access',
                    'multiple' => false,
                    'expanded' => true,
                    'choices' => [
                        'private' => true,
                        'shared' => false,
                    ],
                    'data' => $options['private'],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'rename_only' => false,
            'private' => true,
        ]);
    }
}
