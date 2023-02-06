<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\LocaleSwitcher;

class LanguageService
{
    private const ALLOWED_LANGUAGES = ['fr', 'de', 'es', 'en'];

    public function __construct(
        private readonly RequestStack $request,
        private readonly LocaleSwitcher $localeSwitcher
    ) {
    }

    public function setLocaleInSession(string $lang): void
    {
        $locale = $this->getLocaleFromLang($lang);
        $this->request->getCurrentRequest()->setLocale($locale);
        $this->request->getSession()->set('_locale', $locale);
    }

    public function getLocaleFromLang(string $lang): string
    {
        return !in_array($lang, self::ALLOWED_LANGUAGES) ? 'fr' : $lang;
    }

    public function setLocaleFromLang(string $lang): void
    {
        $this->localeSwitcher->setLocale($this->getLocaleFromLang($lang));
    }
}
