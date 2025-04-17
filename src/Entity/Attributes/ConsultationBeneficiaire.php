<?php

namespace App\Entity\Attributes;

use App\Entity\Membre;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'consultationbeneficiaire')]
#[ORM\Index(name: 'IDX_A161C745AF81F68', columns: ['beneficiaire_id'])]
#[ORM\Index(name: 'IDX_A161C746A99F74A', columns: ['membre_id'])]
class ConsultationBeneficiaire
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTime $createdAt;

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'consultationBeneficiaires')]
    #[ORM\JoinColumn(name: 'membre_id', referencedColumnName: 'id', nullable: false)]
    private Membre $membre;

    #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'consultationBeneficiaires')]
    #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
    private Beneficiaire $beneficiaire;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMembre(): Membre
    {
        return $this->membre;
    }

    public function setMembre(Membre $membre): static
    {
        $this->membre = $membre;

        return $this;
    }

    public function getBeneficiaire(): Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(Beneficiaire $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }
}
