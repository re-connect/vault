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
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'title',
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'note_content',
                'attr' => [
                    'class' => 'd-none',
                    'data-controller' => 'quill-editor',
                ],
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'private' => true,
        ]);
    }
}
