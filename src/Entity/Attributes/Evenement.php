<?php

namespace App\Entity\Attributes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\State\PersonalDataStateProcessor;
use App\Domain\Anonymization\AnonymizationHelper;
use App\Entity\Creator;
use App\Entity\Membre;
use App\Entity\Rappel;
use App\Validator\Constraints\Evenement as CustomAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'evenement')]
#[ORM\Index(columns: ['deposePar_id'], name: 'IDX_B26681EF2AB781')]
#[ORM\Index(columns: ['beneficiaire_id'], name: 'IDX_B26681E5AF81F68')]
#[ORM\Index(columns: ['membre_id'], name: 'IDX_B26681E6A99F74A')]
#[CustomAssert\Entity]
#[ApiResource(
    shortName: 'Event',
    operations: [
        new Delete(security: "is_granted('UPDATE', object)"),
        new Get(security: "is_granted('ROLE_OAUTH2_EVENTS') or is_granted('UPDATE', object)"),
        new GetCollection(security: "is_granted('ROLE_OAUTH2_EVENTS') or is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_OAUTH2_BENEFICIARIES')", processor: PersonalDataStateProcessor::class),
        new Patch(security: "is_granted('UPDATE', object)"),
    ],
    normalizationContext: ['groups' => ['v3:event:read']],
    denormalizationContext: ['groups' => ['v3:event:write']],
    openapiContext: ['tags' => ['Évènements']],
)]
#[ApiResource(
    uriTemplate: '/beneficiaries/{id}/events',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'evenements',
            fromClass: Beneficiaire::class
        ),
    ],
    normalizationContext: ['groups' => ['v3:event:read']],
    denormalizationContext: ['groups' => ['v3:event:write']],
    openapiContext: ['tags' => ['Events']],
    security: "is_granted('ROLE_OAUTH2_BENEFICIARIES')",
)]
class Evenement extends DonneePersonnelle
{
    public const string EVENEMENT_RAPPEL_SMS = 'SMS';
    public const string EVENEMENT_RAPPEL_MAIL = 'Mail';

    #[ORM\Column(name: 'date', type: 'datetime', nullable: false)]
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    #[Assert\Type(\DateTimeInterface::class)]
    private \DateTime $date;

    #[ORM\Column(name: 'timezone', type: 'string', length: 255, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2'])]
    private ?string $timezone = null;

    #[ORM\Column(name: 'lieu', type: 'string', length: 255, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private ?string $lieu = null;

    #[ORM\Column(name: 'commentaire', type: 'text', length: 0, nullable: true)]
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    #[Anonymize('string', options: ['sample' => [AnonymizationHelper::ANONYMIZED_CONTENT]])]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(name: 'membre_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Membre $membre = null;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Rappel::class, cascade: ['persist', 'remove'])]
    #[Groups(['read-personal-data', 'write-personal-data', 'read-personal-data-v2', 'write-personal-data-v2', 'v3:event:write', 'v3:event:read'])]
    private Collection $rappels;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Creator::class, cascade: ['persist', 'remove'])]
    protected Collection $creators;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Beneficiaire::class, inversedBy: 'evenements')]
        #[ORM\JoinColumn(name: 'beneficiaire_id', referencedColumnName: 'id', nullable: false)]
        protected ?Beneficiaire $beneficiaire = null,
    ) {
        parent::__construct();
        $this->date = new \DateTime();
        $this->rappels = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getDate(): \DateTime
    {
        $timezone = $this->timezone ?? 'Europe/Paris';
        $date = $this->date ?? new \DateTime();

        return new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone));
    }

    public function setDate($date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu($lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getRappelsToString(): string
    {
        $str = '';
        if (null !== $this->rappels) {
            foreach ($this->rappels as $rappel) {
                if ('' !== $str) {
                    $str .= ', ';
                }
                $str .= $rappel;
            }
        }

        return $str;
    }

    #[\Override]
    public function __toString(): string
    {
        if ($this->date) {
            return sprintf('%s le %s', $this->nom, $this->date->format('d/m/Y H:i'));
        }

        return (string) $this->nom;
    }

    public function getMembre(): ?Membre
    {
        return $this->membre;
    }

    public function setMembre(?Membre $membre = null): static
    {
        $this->membre = $membre;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'b_prive' => $this->bPrive,
            'nom' => $this->nom,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'commentaire' => $this->commentaire,
            'lieu' => $this->lieu,
            'date' => $this->getDate()->format(\DateTime::W3C),
            'dateToString' => $this->getDateToString(),
            'rappels' => $this->getRappels(false)->toArray(),
            'beneficiaire_id' => $this->getBeneficiaire()->getId(),
        ];
    }

    #[Groups(['read-personal-data-v2', 'v3:event:read'])]
    #[SerializedName('dateToString')]
    public function getDateToString(): string
    {
        setlocale(LC_TIME, 'fr_FR');
        $str = '';
        if (null !== $this->date) {
            $toDay = date_format(new \DateTime(), 'd/m/Y');
            $date = date_format($this->date, 'd/m/Y');
            if ($toDay === $date) {
                $str = 'Aujourd\'hui';
            } else {
                $str = $date = date_format($this->date, 'd M Y');
            }
        }

        return $str;
    }

    /**
     * @return Collection|Rappel[]
     */
    public function getRappels($archive = true)
    {
        $criteria = Criteria::create()->orderBy(['date' => Criteria::ASC]);
        if (!$archive) {
            $criteria->andWhere(Criteria::expr()->eq('archive', false));
        }

        return $this->rappels->matching($criteria);
    }

    /**
     * @param Rappel[]|Collection $rappels
     */
    public function setRappels($rappels): static
    {
        $this->rappels = $rappels;

        return $this;
    }

    public function addRappel(Rappel $rappel): static
    {
        $this->rappels[] = $rappel;
        $rappel->setEvenement($this);

        return $this;
    }

    public function removeRappel(Rappel $rappel)
    {
        return $this->rappels->removeElement($rappel);
    }

    public function getDateToUtcTimezone(): \DateTime
    {
        return $this->getDate()->setTimezone(new \DateTimeZone('UTC'));
    }
}
