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

    #[Groups(['v3:beneficiary:write'])]
    public ?string $password = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $plainPassword = null;

    /** @var ?string[] */
    #[Groups(['v3:beneficiary:write'])]
    public ?array $centers = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $externalCenter = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $externalProId = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $secretQuestion = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $secretQuestionCustomText = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $secretQuestionAnswer = null;

    public function toBeneficiary(): Beneficiaire
    {
        return (new Beneficiaire())
            ->setDateNaissance($this->birthDate)
            ->setDistantId($this->distantId)
            ->setQuestionSecrete($this->secretQuestionCustomText ?? $this->secretQuestion)
            ->setReponseSecrete($this->secretQuestionAnswer)
            ->setUser((new User())
                ->setNom($this->lastName)
                ->setPrenom($this->firstName)
                ->setPlainPassword($this->plainPassword)
                ->setCurrentPassword($this->password)
                ->setEmail($this->email)
                ->setTelephone($this->phone)
                ->setRelaysIds($this->centers ?? [])
                ->setExternalRelayId($this->externalCenter)
                ->setExternalProId($this->externalProId)
            );
    }
}
