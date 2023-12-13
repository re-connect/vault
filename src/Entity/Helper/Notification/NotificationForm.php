<?php

namespace App\Entity\Helper\Notification;

use Symfony\Component\Form\FormView;

class NotificationForm
{
    public function __construct(
        public readonly ?FormView $formView = null,
        public readonly string $stimulusControllers = '',
    ) {
    }
}
