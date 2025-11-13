<?php

namespace App\Sanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

class NoteAttributeSanitizer implements AttributeSanitizerInterface
{
    private const string ALLOWED_CSS_PROPERTY = 'color';

    #[\Override]
    public function getSupportedElements(): ?array
    {
        return null;
    }

    #[\Override]
    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    #[\Override]
    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if (!$value) {
            return null;
        }

        $cssRules = array_map('trim', explode(';', $value));

        foreach ($cssRules as $rule) {
            if ('' === $rule) {
                continue;
            }

            if (!str_starts_with($rule, self::ALLOWED_CSS_PROPERTY)) {
                return null;
            }
        }

        return $value;
    }
}
