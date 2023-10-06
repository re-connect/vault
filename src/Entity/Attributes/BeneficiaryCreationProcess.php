<?php

namespace App\Entity\Attributes;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\RepositoryV2\BeneficiaryCreationProcessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BeneficiaryCreationProcessRepository::class)]
#[ORM\Table(name: 'beneficiary_creation_process')]
class BeneficiaryCreationProcess
{
    public const DEFAULT_TOTAL_STEPS = 6;
    public const DEFAULT_TOTAL_FORM_STEPS = 4;
    public const IDENTITY_STEP = 1;
    public const PASSWORD_STEP = 2;
    public const SECRET_QUESTION_STEP = 3;
    public const DEFAULT_STEP_TITLES = [
        1 => 'fill_beneficiary_identity_information',
        2 => 'choose_beneficiary_password',
        3 => 'choose_beneficiary_secret_question',
        4 => 'choose_beneficiary_centers',
        5 => 'summary_confirm_information',
    ];
    public const DEFAULT_BREADCRUMB_STEPS = [
        1 => 'identity',
        2 => 'password',
        3 => 'secret_question',
        4 => 'relays',
        5 => 'summary',
    ];
    public const RELAYS_STEP = 4;
    public const REMOTELY_RELAYS_STEP = 2;

    public const REMOTELY_TOTAL_STEPS = 4;
    public const REMOTELY_TOTAL_FORM_STEPS = 2;
    public const REMOTELY_STEP_TITLES = [
        1 => 'fill_beneficiary_identity_information',
        2 => 'choose_beneficiary_centers',
        3 => 'summary_confirm_information',
    ];
    public const REMOTELY_BREADCRUMB_STEPS = [
        1 => 'identity',
        2 => 'relays',
        3 => 'summary',
    ];

    public static function create(User $user, bool $remotely = false): self
    {
        return (new self())
            ->setIsCreating(true)
            ->setBeneficiary((new Beneficiaire())->setCreePar($user))
            ->setRemotely($remotely);
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'creationProcess', targetEntity: Beneficiaire::class, cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Beneficiaire $beneficiary = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isCreating = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $remotely = false;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private ?int $currentStep = 1;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private ?int $lastReachedStep = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeneficiary(): ?Beneficiaire
    {
        return $this->beneficiary;
    }

    public function setBeneficiary(?Beneficiaire $beneficiary): self
    {
        $this->beneficiary = $beneficiary;
        $beneficiary->setCreationProcess($this);

        return $this;
    }

    public function getIsCreating(): ?bool
    {
        return $this->isCreating;
    }

    public function isCreating(): ?bool
    {
        return $this->getIsCreating();
    }

    public function setIsCreating(?bool $isCreating): self
    {
        $this->isCreating = $isCreating;
        $this->beneficiary?->setIsCreating($isCreating);

        return $this;
    }

    public function isRemotely(): ?bool
    {
        return $this->remotely;
    }

    public function setRemotely(?bool $remotely): self
    {
        $this->remotely = $remotely;

        return $this;
    }

    public function getTotalSteps(): ?int
    {
        return $this->remotely ? self::REMOTELY_TOTAL_STEPS : self::DEFAULT_TOTAL_STEPS;
    }

    public function isLastStep(): bool
    {
        return $this->getTotalSteps() === $this->currentStep;
    }

    public function getTotalFormSteps(): ?int
    {
        return $this->remotely ? self::REMOTELY_TOTAL_FORM_STEPS : self::DEFAULT_TOTAL_FORM_STEPS;
    }

    public function isStepWithForm(): bool
    {
        return !$this->isRelaysStep() && $this->getTotalFormSteps() >= $this->currentStep;
    }

    public function getBreadCrumbStepNames(): ?array
    {
        return $this->remotely
            ? self::REMOTELY_BREADCRUMB_STEPS
            : self::DEFAULT_BREADCRUMB_STEPS;
    }

    public function getStepTitle(): string
    {
        $titles = $this->isRemotely()
            ? self::REMOTELY_STEP_TITLES
            : self::DEFAULT_STEP_TITLES;

        return $titles[$this->currentStep];
    }

    public function getCurrentStep(): ?int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(?int $currentStep): self
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    public function isPasswordStep(): bool
    {
        return (self::PASSWORD_STEP === $this->currentStep) && !$this->remotely;
    }

    public function isIdentityStep(): bool
    {
        return self::IDENTITY_STEP === $this->currentStep;
    }

    public function isRelaysStep(): bool
    {
        return $this->currentStep === $this->getRelaysStep();
    }

    public function getRelaysStep(): int
    {
        return $this->remotely ? self::REMOTELY_RELAYS_STEP : self::RELAYS_STEP;
    }

    public function getNextStep(): int
    {
        $nextStep = $this->currentStep + 1;

        return $nextStep > $this->getTotalSteps() ? $this->getTotalSteps() : $nextStep;
    }

    public function getPreviousStep(): int
    {
        $previousStep = $this->currentStep - 1;

        return $previousStep < 0 ? 0 : $previousStep;
    }

    public function isLastRemotelyStep(): bool
    {
        return $this->remotely && $this->isLastStep();
    }

    public function getLastReachedStep(): ?int
    {
        return $this->lastReachedStep;
    }

    public function setLastReachedStep(?int $lastReachedStep): self
    {
        $this->lastReachedStep = max($lastReachedStep, $this->lastReachedStep);

        return $this;
    }

    public function isCurrentStep(int $step): bool
    {
        return $step === $this->currentStep;
    }

    public function isSummaryStep(): bool
    {
        return $this->currentStep === ($this->remotely ? self::REMOTELY_TOTAL_STEPS - 1 : self::DEFAULT_TOTAL_STEPS - 1);
    }

    public function isStepDone(int $step): bool
    {
        return $step < $this->lastReachedStep;
    }

    public function isStepReached(int $step): bool
    {
        return $step <= $this->lastReachedStep;
    }

    public function getStepColor(int $step): string
    {
        return match (true) {
            $this->isCurrentStep($step) || $this->isStepDone($step) || $this->isStepReached($step) => 'primary',
            default => 'grey'
        };
    }

    public function getNextUnfilledStep(): int
    {
        return $this->currentStep === $this->lastReachedStep
            ? $this->getNextStep()
            : $this->lastReachedStep;
    }
}
