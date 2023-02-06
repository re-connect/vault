<?php

namespace App\Twig\ExtensionV2;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ButtonExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('button', [$this, 'button'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('link', [$this, 'link'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    /**
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\LoaderError
     */
    public function button(Environment $env, string $type, string $message, string $color): string
    {
        return $env->render('v2/common/_button.html.twig', [
            'type' => $type,
            'message' => $message,
            'color' => $color,
        ]);
    }

    /**
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\LoaderError
     */
    public function link(Environment $env, string $path, string $message, string $color, string $icon = null, string $attr = null): string
    {
        return $env->render('v2/common/_link_button.html.twig', [
            'path' => $path,
            'message' => $message,
            'color' => $color,
            'icon' => $icon,
            'attr' => $attr,
        ]);
    }
}
