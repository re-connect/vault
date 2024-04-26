<?php

namespace App\FormV2\UserAffiliation\Model;

use Symfony\Component\Validator\Constraints as Assert;

class SearchProFormModel
{
    public function __construct(
        #[Assert\NotBlank]
        private ?string $firstname = '',
        #[Assert\NotBlank]
        private ?string $lastname = '',
    ) {
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }
}
