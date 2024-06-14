<?php

namespace App\Twig\ExtensionV2;

use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getUserNavItems', $this->getUserNavItems(...)),
        ];
    }

    public function getUserNavItems(): array
    {
        if (!$user = $this->getUser()) {
            return [];
        }

        $isBeneficiary = $user->isBeneficiaire();
        $navItems = [
            [
                'itemKind' => 'documents-folders',
                'title' => $isBeneficiary ? 'my_documents' : 'documents',
                'routeName' => 'list_documents',
                'image' => 'build/images/icons/docs_bleu.svg',
            ],
            [
                'itemKind' => 'events',
                'title' => $isBeneficiary ? 'my_events' : 'events',
                'routeName' => 'list_events',
                'image' => 'build/images/icons/rappels_bleu.svg',
            ],
            [
                'itemKind' => 'contacts',
                'title' => $isBeneficiary ? 'my_contacts' : 'contacts',
                'routeName' => 'list_contacts',
                'image' => 'build/images/icons/contacts_bleu.svg',
            ],
            [
                'itemKind' => 'notes',
                'title' => $isBeneficiary ? 'my_notes' : 'notes',
                'routeName' => 'list_notes',
                'image' => 'build/images/icons/notes_bleu.svg',
            ],
        ];

        if ($isBeneficiary) {
            $navItems[] = [
                'itemKind' => 'relays',
                'title' => 'my_relays',
                'routeName' => 'my_relays',
                'image' => 'build/images/icons/relais_bleu.svg',
            ];
        }

        return $navItems;
    }
}
