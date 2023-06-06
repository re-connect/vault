<?php

namespace App\FormV2\ResetPassword\PublicRequest;

class ResetPasswordCheckSMSFormModel
{
    public ?string $smsCode = '';

    public function __construct(public readonly string $phone)
    {
    }
}
