<?php

namespace App\Entity;

use App\Repository\RappelRepository;
use App\Validator\Constraints\Rappel as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RappelRepository::class)]
#[ORM\Table(name: 'rappel')]
#[ORM\Index(columns: ['evenement_id'], name: 'IDX_303A29C9FD02F13')]
#[CustomAssert\Entity]
class Rappel implements \JsonSerializable
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['read-personal-data', 'read-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'date', type: 'datetime', nullable: false)]
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'rappels')]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id')]
    private Evenement $evenement;

    #[ORM\Column(name: 'bEnvoye', type: 'boolean', nullable: false)]
    private bool $bEnvoye = false;

    #[ORM\OneToOne(mappedBy: 'rappel', targetEntity: SMS::class, cascade: ['remove'])]
    private ?SMS $sms = null;

    #[ORM\Column(name: 'archive', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $archive = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTime
    {
        $timezone = $this->evenement?->getTimezone() ?? 'Europe/Paris';
        $date = $this->date ?? new \DateTime();

        return new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone));
    }

    public function getDateToUtcTimezone(): \DateTime
    {
        return $this->getDate()->setTimezone(new \DateTimeZone('UTC'));
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getEvenement(): Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement = null): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getBEnvoye(): bool
    {
        return $this->bEnvoye;
    }

    public function setBEnvoye(bool $bEnvoye): static
    {
        $this->bEnvoye = $bEnvoye;

        return $this;
    }

    public function getSms(): ?SMS
    {
        return $this->sms;
    }

    public function setSms(?SMS $sms = null): static
    {
        $this->sms = $sms;

        return $this;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->getDate()->format(\DateTime::W3C),
        ];
    }

    public function getArchive(): bool
    {
        return $this->archive;
    }

    public function setArchive(bool $archive): static
    {
        $this->archive = $archive;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            //            $this->sms = null === $this->sms ? null : clone $this->sms;
            $this->sms = null;
            $this->evenement = null;
        }
    }
}
