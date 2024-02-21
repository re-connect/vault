<?php

namespace App\FormV2\Field;

class PasswordField
{
    public const PASSWORD_STRENGTH_CONTROLLER_DATA_ATTRIBUTES = [
        'data-password-strength-target' => 'input',
        'data-action' => 'password-strength#check',
    ];
}
