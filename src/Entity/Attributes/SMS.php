<?php

namespace App\Entity\Attributes;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'sms')]
#[ORM\Index(columns: ['centre_id'], name: 'IDX_B0A93A77463CD7C3')]
#[ORM\Index(columns: ['beneficiaire_id'], name: 'IDX_B0A93A775AF81F68')]
#[ORM\UniqueConstraint(name: 'UNIQ_B0A93A777A752E96', columns: ['rappel_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_B0A93A77FD02F13', columns: ['evenement_id'])]
class SMS implements \JsonSerializable, \Stringable
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'dest', type: 'string', length: 255, nullable: false)]
    private $dest;

    #[ORM\ManyToOne(targetEntity: Beneficiaire::class)]
    #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
    private ?Beneficiaire $beneficiaire = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id', nullable: true)]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: Centre::class)]
    #[ORM\JoinColumn(name: 'centre_id', referencedColumnName: 'id', nullable: true)]
    private ?Centre $centre = null;

    #[ORM\ManyToOne(targetEntity: Rappel::class)]
    #[ORM\JoinColumn(name: 'rappel_id', referencedColumnName: 'id')]
    private ?Rappel $rappel = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTime $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read', 'timed', 'v3:user:read', 'v3:beneficiary:read'])]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDest(): string
    {
        return $this->dest;
    }

    public function setDest($dest): static
    {
        $this->dest = $dest;

        return $this;
    }

    public function getCentre(): Centre
    {
        return $this->centre;
    }

    /**
     * Set centre.
     */
    public function setCentre(?Centre $centre = null): static
    {
        $this->centre = $centre;

        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getBeneficiaire(): Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Beneficiaire $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    public function getEvenement(): Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    public function getRappel(): Rappel
    {
        return $this->rappel;
    }

    public function setRappel(Rappel $rappel): static
    {
        $this->rappel = $rappel;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->centre = null;
            $this->evenement = null;
            $this->beneficiaire = null;
            $this->rappel = null;
        }
    }

    #[\Override]
    public function __toString(): string
    {
        return 'SMS To String';
    }

    public static function createReminderSms(Rappel $reminder, Evenement $event, Beneficiaire $beneficiary, string $number): static
    {
        return (new SMS())
            ->setRappel($reminder)
            ->setEvenement($event)
            ->setBeneficiaire($beneficiary)
            ->setDest($number);
    }
}
