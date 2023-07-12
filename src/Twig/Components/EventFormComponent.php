<?php

namespace App\Twig\Components;

use App\ControllerV2\AbstractController;
use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\FormV2\EventType;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent('event_form')]
class EventFormComponent extends AbstractController
{
    use LiveCollectionTrait;
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'data')]
    public ?Evenement $event = null;
    #[LiveProp]
    public Beneficiaire $beneficiary;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            EventType::class,
            $this->event
        );
    }

    #[PreReRender]
    public function noFormSubmissionOnRender(): void
    {
        if (!$this->getForm()->isSubmitted()) {
            $this->submitForm(false);
        }
    }
}
