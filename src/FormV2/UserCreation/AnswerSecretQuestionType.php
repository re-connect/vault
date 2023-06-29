<?php

namespace App\FormV2\UserCreation;

use App\Entity\Beneficiaire;
use App\Helper\SecretQuestionsHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnswerSecretQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addFields($builder, $builder->getData());
    }

    public function addFields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $builder
            ->add('questionSecrete', TextType::class, [
                'required' => false,
                'disabled' => true,
                'label' => 'user.parametres.questionSecreteLabel',
            ])
            ->add('autreQuestionSecrete', TextType::class, [
                'disabled' => true,
                'required' => false,
                'label' => 'secret_question_other',
                'mapped' => false,
            ])
            ->add('reponseSecrete', TextType::class, [
                'label' => 'user.parametres.reponseSecreteLabel',
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Beneficiaire::class]);
    }
}
