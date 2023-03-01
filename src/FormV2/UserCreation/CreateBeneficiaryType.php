<?php

namespace App\FormV2\UserCreation;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Form\Event\SecretQuestionListener;
use App\FormV2\UserInformationType;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateBeneficiaryType extends AbstractType
{
    use UserAwareTrait;

    public const DEFAULT_STEP_VALIDATION_GROUP = [2 => ['password', 'password-beneficiaire'], 3 => ['beneficiaireQuestionSecrete']];
    public const REMOTELY_STEP_VALIDATION_GROUP = [1 => ['beneficiaire-remotely']];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $beneficiary = $options['data'];
        $step = $options['step'];
        $remotely = $beneficiary->getCreationProcess()?->isRemotely() ?? false;

        if ($remotely) {
            match ($step) {
                default => $this->addStep1Fields($builder, $remotely),
                2 => $this->addStep4Fields($builder, $beneficiary),
            };
        } else {
            match ($step) {
                default => $this->addStep1Fields($builder, $remotely),
                2 => $this->addStep2Fields($builder, $beneficiary),
                3 => $this->addStep3Fields($builder, $beneficiary),
                4 => $this->addStep4Fields($builder, $beneficiary),
            };
        }
    }

    public function addStep1Fields(FormBuilderInterface $builder, bool $remotely): void
    {
        $builder
            ->add('user', UserInformationType::class, [
                'label' => false,
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'required' => false,
                'label' => 'birthdate',
                'data' => new \DateTime('01/01/1975'),
            ]);

        $builder->get('user')->get('telephone')->setRequired($remotely);
    }

    public function addStep2Fields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $builder
            ->add('password', TextType::class, [
                'property_path' => 'user.plainPassword',
                'label' => 'password',
            ]);
    }

    public function addStep3Fields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $secretQuestions = [];
        foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
            $secretQuestions[$this->translator->trans($key)] = $this->translator->trans($value);
        }

        $builder
            ->add('questionSecreteChoice', ChoiceType::class, [
                'required' => true,
                'label' => 'secret_question',
                'choices' => $secretQuestions,
                'data' => $this->getSecretQuestionDefaultValue($beneficiary, $secretQuestions),
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
            ->addEventSubscriber(new SecretQuestionListener($this->translator));
    }

    public function addStep4Fields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $builder
            ->add('relays', EntityType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->getUser()->getCentres(),
                'class' => Centre::class,
                'label' => false,
                'data' => $beneficiary->getCentres(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'validation_groups' => ['beneficiaire'],
            'cascade_validation' => true,
            'step' => 1,
        ]);
    }

    /**
     * @param array<string, string> $secretQuestions
     */
    private function getSecretQuestionDefaultValue(Beneficiaire $beneficiary, array $secretQuestions): string
    {
        if ($beneficiarySecretQuestion = $beneficiary->getQuestionSecrete()) {
            return array_key_exists($beneficiarySecretQuestion, $secretQuestions)
                ? $beneficiarySecretQuestion
                : $secretQuestions[$this->translator->trans('membre.creationBeneficiaire.questionsSecretes.q9')];
        }

        return array_key_first($secretQuestions);
    }
}
