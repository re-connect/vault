<?php

namespace App\FormV2;

use App\Entity\Beneficiaire;
use App\Validator\Constraints\SecretAnswer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnswerSecretQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $builder->getData();
        $builder
            ->add('reponseSecrete', TextType::class, [
                'label' => 'secret_answer',
                'mapped' => false,
                'constraints' => new SecretAnswer($beneficiary),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Beneficiaire::class]);
    }
}
