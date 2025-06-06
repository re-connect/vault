<?php

namespace App\Form\Type;

use App\Entity\Attributes\DonneePersonnelle;
use App\Entity\Attributes\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierSimpleType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        $builder->add('nom', null, ['label' => 'name', 'label_attr' => ['class' => 'font-size-1']]);

        if (null === $dossier->getDossierParent()) {
            $builder->add('bPrive', ChoiceType::class, [
                'label' => 'access',
                'label_attr' => ['class' => 'font-size-1'],
                'required' => true,
                'expanded' => true,
                'choices' => DonneePersonnelle::getArBPrive(), ]);
        } else {
            $builder->add('bPrive', HiddenType::class, [
                'data' => $dossier->getDossierParent()->getBPrive() ? 1 : 0,
            ]);
        }

        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'confirm',
                'attr' => ['class' => 'btn-green btn-blue font-size-1 js-loading-container'],
            ])
            ->setAction('#');
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_dossiersimple';
    }
}
