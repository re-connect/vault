<?php

namespace App\FormV2\UserCreation;

use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\Entity\Beneficiaire;
use App\FormV2\UserType;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateBeneficiaryType extends AbstractType
{
    use UserAwareTrait;

    public const DEFAULT_STEP_VALIDATION_GROUP = [1 => ['phone'], 2 => ['password', 'password-beneficiaire'], 3 => ['beneficiaireQuestionSecrete']];
    public const REMOTELY_STEP_VALIDATION_GROUP = [1 => ['beneficiaire-remotely', 'phone']];

    public function __construct(
        private readonly SecretQuestionType $secretQuestionType,
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $options['data'];
        $creationProcess = $beneficiary->getCreationProcess();

        match ($creationProcess?->getCurrentStep() ?? 1) {
            default => $this->addIdentityFields($builder, $beneficiary->getDateNaissance(), $creationProcess?->isRemotely()),
            2 => $this->addStep2Fields($builder, $beneficiary, $creationProcess->isRemotely()),
            3 => $this->secretQuestionType->addFields($builder, $beneficiary),
        };
    }

    public function addIdentityFields(FormBuilderInterface $builder, ?\DateTime $birthDate, ?bool $remotely = false): void
    {
        $builder
            ->add('user', UserType::class, [
                'label' => false,
                'attr' => ['class' => 'row'],
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'label' => 'birthDate',
                'row_attr' => ['class' => 'mt-3'],
                'data' => $birthDate,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'validation_groups' => ['beneficiaire'],
        ]);
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

    private function addStep2Fields(FormBuilderInterface $builder, Beneficiaire $beneficiary, bool $isRemotely): void
    {
        $this->addPasswordFields($builder);
    }
}
