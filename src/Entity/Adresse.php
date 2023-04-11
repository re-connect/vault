<?php

namespace App\Entity;

use JsonSerializable;
use Symfony\Component\Serializer\Annotation\Groups;

class Adresse implements \JsonSerializable
{
    #[Groups('read')]
    private ?int $id;

    #[Groups('read')]
    private ?string $nom = null;

    #[Groups('read')]
    private ?string $ville = null;

    #[Groups('read')]
    private ?string $codePostal = null;

    #[Groups('read')]
    private ?string $pays = null;

    #[Groups('read')]
    private ?float $lat = null;

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

    public function __toString(): string
    {
        return sprintf(
            '%s %s %s %s',
            $this->nom,
            $this->ville,
            $this->codePostal,
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
