<?php

namespace App\FormV2\UserCreation;

use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Form\Event\SecretQuestionListener;
use App\FormV2\UserInformationType;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
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
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $options['data'];
        $creationProcess = $beneficiary->getCreationProcess();

        match ($creationProcess?->getCurrentStep() ?? 1) {
            default => $this->addIdentityFields($builder, $beneficiary->getDateNaissance(), $creationProcess?->isRemotely()),
            2 => $creationProcess?->isRemotely()
                ? $this->addRelaysFields($builder, $beneficiary->getCentres())
                : $this->addPasswordFields($builder),
            3 => $this->addSecretQuestionFields($builder, $beneficiary),
            4 => $this->addRelaysFields($builder, $beneficiary->getCentres()),
        };
    }

    public function addIdentityFields(FormBuilderInterface $builder, ?\DateTime $birthDate, ?bool $remotely = false): void
    {
        $builder
            ->add('user', UserInformationType::class, [
                'label' => false,
                'attr' => ['class' => 'row'],
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'required' => false,
                'label' => 'birthdate',
                'row_attr' => ['class' => 'mt-3'],
                'data' => $birthDate ?? new \DateTime('01/01/1975'),
            ]);

        $builder->get('user')->get('telephone')->setRequired($remotely);
    }

    public function addPasswordFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('password', TextType::class, [
                'property_path' => 'user.plainPassword',
                'label' => 'password',
            ]);
    }

    public function addSecretQuestionFields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $secretQuestions = $this->getSecretQuestions();
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

    /** @param Collection<int, Centre> $relays */
    public function addRelaysFields(FormBuilderInterface $builder, Collection $relays): void
    {
        $builder
            ->add('relays', EntityType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->getUser()->getCentres(),
                'choice_label' => 'nameAndAddress',
                'class' => Centre::class,
                'label' => false,
                'data' => $relays,
                'row_attr' => ['class' => 'relay-checkboxes'],
                'label_attr' => ['class' => 'btn btn-outline-primary no-hover'],
                'choice_attr' => fn () => ['class' => 'btn-check'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'validation_groups' => ['beneficiaire'],
            'cascade_validation' => true,
        ]);
    }

    /** @param array<string, string> $secretQuestions */
    private function getSecretQuestionDefaultValue(Beneficiaire $beneficiary, array $secretQuestions): string
    {
        if ($beneficiarySecretQuestion = $beneficiary->getQuestionSecrete()) {
            return array_key_exists($beneficiarySecretQuestion, $secretQuestions)
                ? $beneficiarySecretQuestion
                : $secretQuestions[$this->translator->trans('membre.creationBeneficiaire.questionsSecretes.q9')];
        }

        return array_key_first($secretQuestions);
    }

    /** @return string[] */
    public static function getStepValidationGroup(BeneficiaryCreationProcess $beneficiaryCreationProcess): array
    {
        $validationGroup = $beneficiaryCreationProcess->isRemotely()
            ? self::REMOTELY_STEP_VALIDATION_GROUP
            : self::DEFAULT_STEP_VALIDATION_GROUP;

        return [
            'beneficiaire',
            ...$validationGroup[$beneficiaryCreationProcess->getCurrentStep()] ?? [],
        ];
    }

    /** @return array<string, string> */
    public function getSecretQuestions(): array
    {
        $secretQuestions = [];
        foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
            $secretQuestions[$this->translator->trans($key)] = $this->translator->trans($value);
        }

        return $secretQuestions;
    }
}
