<?php

namespace App\Form\Type;

use App\Entity\Beneficiaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaireParametresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', UserBeneficiaireWithoutPasswordType::class, [
                'label' => 'user.parametres.userLabel',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'required' => false,
                'label' => 'birthDate',
                'attr' => ['class' => 'datePicker'],
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add('questionSecrete', null, [
                'label' => 'secret_question',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add('reponseSecrete', null, [
                'label' => 'secret_answer',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add('submit', SubmitType::class, ['label' => 'user.parametres.enregister', 'attr' => ['class' => 'btn-green font-size-1']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'validation_groups' => ['beneficiaire'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_beneficiaireparametres';
    }
}
