<?php

namespace App\FormV2\UserCreation;

use App\Entity\Beneficiaire;
use App\Helper\SecretQuestionsHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SecretQuestionType extends AbstractType
{
    public function __construct(private readonly SecretQuestionsHelper $secretQuestionsHelper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addFields($builder, $builder->getData());
    }

    public function addFields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $secretQuestions = $this->secretQuestionsHelper->getSecretQuestions();
        $builder
            ->add('questionSecreteChoice', ChoiceType::class, [
                'required' => true,
                'label' => 'secret_question',
                'choices' => $secretQuestions,
                'data' => $this->secretQuestionsHelper->getSecretQuestionDefaultValue($beneficiary, $secretQuestions),
                'mapped' => false,
                'attr' => [
                    'data-conditional-field-target' => 'conditionalField',
                    'data-conditional-value' => array_key_last($secretQuestions),
                    'data-action' => 'conditional-field#update',
                ],
            ])
            ->add('questionSecrete', HiddenType::class, [
                'required' => true,
                'label' => 'user.parametres.questionSecreteLabel',
            ])
            ->add('autreQuestionSecrete', TextType::class, [
                'required' => false,
                'label' => 'secret_question_other',
                'mapped' => false,
                'row_attr' => [
                    'data-conditional-field-target' => 'conditionedField',
                ],
            ])
            ->add('reponseSecrete', TextType::class, [
                'label' => 'user.parametres.reponseSecreteLabel',
            ])
            ->addEventSubscriber($this->secretQuestionsHelper->createSecretQuestionListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Beneficiaire::class]);
    }
}
