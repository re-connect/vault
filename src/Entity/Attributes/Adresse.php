<?php

namespace App\Entity\Attributes;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'adresse')]
#[ORM\Entity(repositoryClass: AdresseRepository::class)]
class Adresse implements \JsonSerializable, \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('read')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: true)]
    #[Groups('read')]
    private ?string $nom = null;

    #[ORM\Column(name: 'ville', type: 'string', length: 255, nullable: true)]
    #[Groups('read')]
    private ?string $ville = null;

    #[ORM\Column(name: 'codePostal', type: 'string', length: 255, nullable: true)]
    #[Groups('read')]
    private ?string $codePostal = null;

    #[ORM\Column(name: 'pays', type: 'string', length: 255, nullable: true)]
    #[Groups('read')]
    private ?string $pays = null;

    #[ORM\Column(name: 'lat', type: 'float', precision: 10, scale: 0, nullable: true)]
    #[Groups('read')]
    private ?float $lat = null;

    #[ORM\Column(name: 'lng', type: 'float', precision: 10, scale: 0, nullable: true)]
    #[Groups('read')]
    private ?float $lng = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf(
            '%s %s %s %s',
            $this->nom,
            $this->codePostal,
            $this->ville,
            $this->pays
        );
    }

    public function toHTML(): string
    {
        return sprintf(
            '%s <br/>%s %s <br/>%s',
            $this->nom,
            $this->codePostal,
            $this->ville,
            $this->pays
        );
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(?float $lng): self
    {
        $this->lng = $lng;

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
            'nom' => $this->nom,
            'ville' => $this->ville,
            'code_postal' => $this->codePostal,
            'pays' => $this->pays,
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
