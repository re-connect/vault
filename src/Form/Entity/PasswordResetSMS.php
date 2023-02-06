<?php

namespace App\Form\Entity;

class PasswordResetSMS
{
    private string $smsCode;
    private string $password;

    public function getSmsCode(): string
    {
        return $this->smsCode;
    }

    public function setSmsCode(string $smsCode): self
    {
        $this->smsCode = $smsCode;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
