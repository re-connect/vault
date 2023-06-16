<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaireSearchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $years = [];
//        for ($i = 1900; $i < 2010; $i++) {
//            $years[] = $i;
//        }

        $builder
            ->add('nom', null, ['required' => false, 'label' => 'name'])
            ->add('prenom', null, ['required' => false, 'label' => 'firstname'])
//            ->add('dateNaissance', DateType::class, ['required' => false, 'label' => 'membre.ajoutBeneficiaire.dateNaissanceLabel', 'attr' => ['class' => 'datePicker'], 'years' => $years])
            ->add('dateNaissance', BirthdayType::class, ['required' => false, 'label' => 'membre.ajoutBeneficiaire.dateNaissanceLabel', 'attr' => ['class' => 'datePicker']])
            ->add('rechercher', SubmitType::class, ['label' => 'search']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }

    public function getName(): string
    {
        return 're_form_beneficiaireSearch';
    }
}
