<?php

namespace App\FormV2\UserAffiliation\Model;

class SearchProFormModel
{
    public function __construct(
        private ?string $firstname = '',
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
