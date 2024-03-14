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
     * @param array<int, string>   $recipients
     * @param array<string, mixed> $extraContent
     */
    public static function create(array $recipients, string $lang, string $url, User $sender = null, array $extraContent = []): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to(...$recipients)
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
