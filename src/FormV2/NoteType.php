<?php

namespace App\FormV2;

use App\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'note.nomLabel',
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'note.contenuLabel',
            ])
            ->add('bPrive', ChoiceType::class, [
                'label' => 'access',
                'multiple' => false,
                'expanded' => true,
                'choices' => [
                    'private' => true,
                    'shared' => false,
                ],
                'data' => $options['private'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'private' => true,
        ]);
    }
}
