<?php

namespace App\Twig\ExtensionV2;

use App\Entity\Attributes\Contact;
use App\Entity\Attributes\Document;
use App\Entity\Attributes\DonneePersonnelle;
use App\Entity\Attributes\Note;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PersonalDataActionExtension extends AbstractExtension
{
    use UserAwareTrait;

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPersonalDataActions', $this->getPersonalDataActions(...)),
        ];
    }

    private function getPersonalDataActions(DonneePersonnelle $personalData): array
    {
        $user = $this->getUser();

        if (!$user) {
            return [];
        }

        return match ($personalData::class) {
            Document::class => $this->getDocumentActions($personalData),
            Dossier::class => $this->getFolderActions($personalData),
            Contact::class => $this->getContactActions($personalData),
            Note::class => $this->getNoteActions($personalData),
            Evenement::class => $this->getEventActions($personalData),
        };
    }

    private function getDocumentActions(Document $document): array
    {
        return [
            'delete' => $this->authorizationChecker->isGranted('DELETE', $document)
                ? $this->getDeleteAction(
                    $this->urlGenerator->generate('document_delete', ['id' => $document->getId()]),
                    $this->translator->trans('modal_message_document_deletev2', ['%name%' => $document->getNom()]),
                )
                : [],
            'edit' => $this->getEditAction(
                $this->urlGenerator->generate('document_rename', ['id' => $document->getId()]),
                $this->translator->trans('rename'),
            ),
            'detail' => $this->getDetailAction($this->urlGenerator->generate('document_detail', ['id' => $document->getId()])),
            'send' => $this->getSendAction($this->urlGenerator->generate('document_share', ['id' => $document->getId()])),
            'move' => $this->getMoveAction($this->urlGenerator->generate('document_tree_view_move', ['id' => $document->getId()])),
            'switchPrivate' => !$this->getUser()->isBeneficiaire() && !$document->getDossier()
                ? $this->getSwitchPrivateAction($this->urlGenerator->generate('document_toggle_visibility', ['id' => $document->getId()]))
                : [],
        ];
    }

    private function getFolderActions(Dossier $folder): array
    {
        return [
            'delete' => $this->authorizationChecker->isGranted('DELETE', $folder)
                ? $this->getDeleteAction(
                    $this->urlGenerator->generate('folder_delete', ['id' => $folder->getId()]),
                    $this->translator->trans($folder->hasDocuments() ? 'folder_contains_documents_alert' : 'modal_message_folder_deletev2', ['%name%' => $folder->getNom()]),
                )
                : [],
            'edit' => $this->getEditAction(
                $this->urlGenerator->generate('folder_rename', ['id' => $folder->getId()]),
                $this->translator->trans('edit'),
            ),
            'detail' => $this->getDetailAction($this->urlGenerator->generate('folder_detail', ['id' => $folder->getId()])),
            'move' => $this->getMoveAction($this->urlGenerator->generate('folder_tree_view_move', ['id' => $folder->getId()])),
            'switchPrivate' => !$this->getUser()->isBeneficiaire() && !$folder->getDossierParent()
                ? $this->getSwitchPrivateAction($this->urlGenerator->generate('folder_toggle_visibility', ['id' => $folder->getId()]))
                : [],
        ];
    }

    private function getContactActions(Contact $contact): array
    {
        return [
            'delete' => $this->getDeleteAction(
                $this->urlGenerator->generate('contact_delete', ['id' => $contact->getId()]),
                $this->translator->trans('modal_message_contact_deletev2', ['%fullName%' => $contact->getFullName()]),
            ),
            'edit' => $this->getEditAction(
                $this->urlGenerator->generate('contact_edit', ['id' => $contact->getId()]),
                $this->translator->trans('edit'),
            ),
            'detail' => $this->getDetailAction($this->urlGenerator->generate('contact_detail', ['id' => $contact->getId()])),
            'switchPrivate' => !$this->getUser()->isBeneficiaire()
                ? $this->getSwitchPrivateAction($this->urlGenerator->generate('contact_toggle_visibility', ['id' => $contact->getId()]))
                : [],
        ];
    }

    private function getNoteActions(Note $note): array
    {
        return [
            'delete' => $this->getDeleteAction(
                $this->urlGenerator->generate('note_delete', ['id' => $note->getId()]),
                $this->translator->trans('modal_message_note_deletev2', ['%name%' => $note->getNom()]),
            ),
            'edit' => $this->getEditAction(
                $this->urlGenerator->generate('note_edit', ['id' => $note->getId()]),
                $this->translator->trans('edit'),
            ),
            'detail' => $this->getDetailAction($this->urlGenerator->generate('note_detail', ['id' => $note->getId()])),
            'switchPrivate' => !$this->getUser()->isBeneficiaire()
                ? $this->getSwitchPrivateAction($this->urlGenerator->generate('note_toggle_visibility', ['id' => $note->getId()]))
                : [],
        ];
    }

    private function getEventActions(Evenement $event): array
    {
        return [
            'delete' => $this->getDeleteAction(
                $this->urlGenerator->generate('event_delete', ['id' => $event->getId()]),
                $this->translator->trans('modal_message_event_deletev2', ['%name%' => $event->getNom()]),
            ),
            'edit' => $this->getEditAction(
                $this->urlGenerator->generate('event_edit', ['id' => $event->getId()]),
                $this->translator->trans('edit'),
            ),
            'detail' => $this->getDetailAction($this->urlGenerator->generate('event_detail', ['id' => $event->getId()])),
            'switchPrivate' => !$this->getUser()->isBeneficiaire()
                ? $this->getSwitchPrivateAction($this->urlGenerator->generate('event_toggle_visibility', ['id' => $event->getId()]))
                : [],
        ];
    }

    private function getDeleteAction(string $url, string $confirmMessage): array
    {
        return [
            'icon' => 'trash',
            'text' => $this->translator->trans('delete'),
            'path' => $url,
            'confirmMessage' => $confirmMessage,
            'confirmButtonText' => $this->translator->trans('delete'),
        ];
    }

    private function getEditAction(string $url, string $text): array
    {
        return [
            'icon' => 'pencil-alt',
            'text' => $text,
            'path' => $url,
        ];
    }

    private function getDetailAction(string $url): array
    {
        return [
            'icon' => 'info-circle',
            'text' => $this->translator->trans('information'),
            'path' => $url,
        ];
    }

    private function getSendAction(string $url): array
    {
        return [
            'icon' => 'paper-plane',
            'text' => $this->translator->trans('send'),
            'path' => $url,
        ];
    }

    private function getMoveAction(string $url): array
    {
        return [
            'icon' => 'folder-open',
            'text' => $this->translator->trans('move'),
            'path' => $url,
        ];
    }

    private function getSwitchPrivateAction(string $url): array
    {
        return [
            'icon' => 'exclamation-triangle',
            'text' => $this->translator->trans('switch_content_private'),
            'path' => $url,
            'confirmMessage' => $this->translator->trans('switch_content_private_confirm'),
            'confirmButtonText' => $this->translator->trans('toggle'),
        ];
    }
}
