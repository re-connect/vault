<?php

namespace App\Form\Type;

use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentSimpleType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'name'])
            ->add('bPrive', ChoiceType::class, [
                'label' => 'access',
                'required' => true,
                'expanded' => true,
                'choices' => DonneePersonnelle::getArBPrive(),
            ])
            ->add('submit', SubmitType::class, ['label' => 'confirm'])
            ->add('cancel', SubmitType::class, ['label' => 'cancel'])
            ->setAction('#');
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_documentsimple';
    }
}
