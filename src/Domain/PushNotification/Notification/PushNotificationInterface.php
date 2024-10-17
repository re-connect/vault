<?php

namespace App\Domain\PushNotification\Notification;

interface PushNotificationInterface
{
    public function getTitle(): string;

    public function getMessage(): string;

    public function getRecipient(): string;

    public function canBeSent(): bool;
}
