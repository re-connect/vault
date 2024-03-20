<?php

namespace App\Api\Dto;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Validator\Constraints\UniqueExternalLink;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueExternalLink]
final class BeneficiaryDto
{
    #[Assert\NotNull]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['v3:beneficiary:write'])]
    public ?\DateTime $birthDate = null;

    #[Assert\NotNull]
    #[Groups(['v3:beneficiary:write'])]
    public ?string $firstName = null;

    #[Assert\NotNull]
    #[Groups(['v3:beneficiary:write'])]
    public ?string $lastName = null;

    #[Assert\Email]
    #[Groups(['v3:beneficiary:write'])]
    public ?string $email = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $phone = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $distantId = null;

    public function toBeneficiary(): Beneficiaire
    {
        return (new Beneficiaire())
            ->setUser((new User())
                ->setNom($this->lastName)
                ->setPrenom($this->firstName)
                ->setEmail($this->email)
                ->setTelephone($this->phone)
            )
            ->setDateNaissance($this->birthDate)
            ->setDistantId($this->distantId);
    }
}
