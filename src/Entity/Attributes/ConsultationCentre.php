<?php

namespace App\Entity\Attributes;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'consultationcentre')]
#[ORM\Index(name: 'IDX_3702E4C7463CD7C3', columns: ['centre_id'])]
#[ORM\Index(name: 'IDX_3702E4C75AF81F68', columns: ['beneficiaire_id'])]
class ConsultationCentre
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: \Centre::class, inversedBy: 'consultationsCentre')]
    #[ORM\JoinColumn(name: 'centre_id', referencedColumnName: 'id', nullable: false)]
    private Centre $centre;

    #[ORM\ManyToOne(targetEntity: \Beneficiaire::class, inversedBy: 'consultationsCentre')]
    #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
    private Beneficiaire $beneficiaire;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    private \DateTime $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCentre(Centre $centre): static
    {
        $this->centre = $centre;

        return $this;
    }

    public function getCentre(): Centre
    {
        return $this->centre;
    }

    public function setBeneficiaire(Beneficiaire $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    public function getBeneficiaire(): Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
