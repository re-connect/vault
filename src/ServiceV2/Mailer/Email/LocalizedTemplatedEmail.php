<?php

namespace App\ServiceV2\Mailer\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

abstract class LocalizedTemplatedEmail
{
    protected const TEMPLATE_PATH = null;
    protected const SUBJECT = null;
    protected const TRANSLATION_ROUTE = null;

    /**
     * @param array<string, string> $extraContent
     */
    public static function create(string $recipient, string $lang, string $url, User $sender = null, array $extraContent = []): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($recipient)
            ->subject(static::SUBJECT)
            ->htmlTemplate(static::TEMPLATE_PATH)
            ->context(array_merge(self::getContext($lang, $url, $sender), $extraContent));
    }

    /**
     * @return string[]
     */
    public static function getContext(string $lang, string $url, User $sender = null): array
    {
        return [
            'url' => $url,
            'year' => (new \DateTime())->format('Y'),
            'userLang' => $lang,
            'translationRoute' => static::TRANSLATION_ROUTE,
            'senderFullName' => $sender?->getFullName(),
        ];
    }
}
