<?php

namespace App\Api\Dto;

use App\Entity\Attributes\Centre;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CentreDto
{
    #[Assert\NotNull]
    #[Groups(['v3:center:write'])]
    public string $nom;

    #[Groups(['v3:center:write'])]
    public ?string $association = null;

    public function toCentre(): Centre
    {
        return (new Centre())->setNom($this->nom);
    }
}
