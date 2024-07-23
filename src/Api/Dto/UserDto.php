<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
final class UserDto
{
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['v3:user:write'])]
    public ?\DateTime $birthDate = null;

    #[Groups(['v3:user:write'])]
    public ?string $firstName = null;

    #[Groups(['v3:user:write'])]
    public ?string $lastName = null;

    #[Assert\Email]
    #[Groups(['v3:user:write'])]
    public ?string $email = null;

    #[Groups(['v3:user:write'])]
    public ?string $phone = null;

    #[Groups(['v3:user:write'])]
    public ?string $password = null;

    #[Groups(['v3:user:write'])]
    public ?string $newPassword = null;

    #[Groups(['v3:user:write'])]
    public ?string $secretQuestion = null;

    #[Groups(['v3:user:write'])]
    public ?string $secretQuestionCustomText = null;

    #[Groups(['v3:user:write'])]
    public ?string $secretQuestionAnswer = null;

    public function toUser(): User
    {
        return (new User())
            ->setBirthDate($this->birthDate)
            ->setSecretQuestion($this->secretQuestionCustomText ?? $this->secretQuestion)
            ->setSecretAnswer($this->secretQuestionAnswer)
            ->setNom($this->lastName)
            ->setPrenom($this->firstName)
            ->setPlainPassword($this->password)
            ->setEmail($this->email)
            ->setTelephone($this->phone);
    }

    public function patchUser(User $user): User
    {
        if ($this->birthDate) {
            $user->setBirthDate($this->birthDate);
        }
        if ($this->secretQuestionCustomText ?? $this->secretQuestion) {
            $user->setSecretQuestion($this->secretQuestionCustomText ?? $this->secretQuestion);
        }
        if ($this->secretQuestionAnswer) {
            $user->setSecretAnswer($this->secretQuestionAnswer);
        }
        if ($this->lastName) {
            $user->setNom($this->lastName);
        }
        if ($this->firstName) {
            $user->setPrenom($this->firstName);
        }
        if ($this->password) {
            $user->setCurrentPassword($this->password);
        }
        if ($this->newPassword) {
            $user->setPlainPassword($this->newPassword);
        }
        if ($this->email) {
            $user->setEmail($this->email);
        }
        if ($this->phone) {
            $user->setTelephone($this->phone);
        }

        return $user;
    }
}
