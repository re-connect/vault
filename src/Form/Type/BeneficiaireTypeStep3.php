<?php

namespace App\Form\Type;

use App\Entity\Beneficiaire;
use App\Form\DataTransformer\QuestionSecreteTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class BeneficiaireTypeStep3 extends AbstractType
{
    private TranslatorInterface $translator;
    private Request $request;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $arQuestions = [];
        foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
            $arQuestions[$this->translator->trans($key)] = $this->translator->trans($value);
        }

        $builder
            ->add('questionSecrete', ChoiceType::class, ['required' => true, 'label' => 'secret_question', 'choices' => $arQuestions])
            ->add('autreQuestionSecrete', TextType::class, ['required' => false, 'label' => 'secret_question_other', 'mapped' => false])
            ->add('reponseSecrete', TextType::class, ['required' => true, 'label' => 'secret_answer'])
            ->add('submit', SubmitType::class, ['label' => 'confirm'])
            ->addModelTransformer(new QuestionSecreteTransformer($this->request));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_beneficiaire_step3';
    }
}
