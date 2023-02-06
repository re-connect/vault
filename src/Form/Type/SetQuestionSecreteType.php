<?php

namespace App\Form\Type;

use App\Entity\Beneficiaire;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetQuestionSecreteType extends BeneficiaireTypeStep3
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('user', UserSetPasswordType::class, ['label' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'validation_groups' => ['beneficiaireQuestionSecrete', 'password'],
        ]);
    }
}
