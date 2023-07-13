<?php

namespace App\Form\Type;

use App\Entity\DonneePersonnelle;
use App\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteSimpleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'note.nomLabel',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'note.contenuLabel',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add('bPrive', ChoiceType::class, [
                'label' => 'access',
                'label_attr' => ['class' => 'font-size-1'],
                'required' => true, 'expanded' => true,
                'choices' => DonneePersonnelle::getArBPrive(),
                'data' => DonneePersonnelle::PRIVE,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'confirm',
                'attr' => ['class' => 'btn-blue btn-green font-size-1 js-loading-container'],
            ])
            ->setAction('#');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
        ]);
    }

    public function getName(): string
    {
        return 're_form_notesimple';
    }
}
