<?php

namespace App\Entity\Attributes;

use App\Entity\Beneficiaire;
use App\RepositoryV2\BeneficiaryCreationProcessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BeneficiaryCreationProcessRepository::class)]
#[ORM\Table(name: 'beneficiary_creation_process')]
class BeneficiaryCreationProcess
{
    public const DEFAULT_TOTAL_STEPS = 6;
    public const DEFAULT_TOTAL_FORM_STEPS = 4;
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

    public function getTotalFormSteps(): ?int
    {
        return $this->remotely ? self::REMOTELY_TOTAL_FORM_STEPS : self::DEFAULT_TOTAL_FORM_STEPS;
    }

    public function getBreadCrumbStepNames(): ?array
    {
        return $this->remotely
            ? self::REMOTELY_BREADCRUMB_STEPS
            : self::DEFAULT_BREADCRUMB_STEPS;
    }

    public function getStepTitle(int $step): string
    {
        $titles = $this->isRemotely()
            ? self::REMOTELY_STEP_TITLES
            : self::DEFAULT_STEP_TITLES;

        return $titles[$step];
    }
}
