<?php

namespace App\Form\Event;

use App\Entity\Beneficiaire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecretQuestionListener implements EventSubscriberInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'onPostSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPostSetData(PostSetDataEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        $beneficiary = $data instanceof Beneficiaire
            ? $data
            : $data->getSubjectBeneficiaire();

        if ($beneficiary) {
            $secretQuestionChoice = $form->get('questionSecreteChoice');
            $choices = $secretQuestionChoice->getConfig()->getOption('choices') ?? [];
            $beneficiaryQuestion = $beneficiary->getQuestionSecrete();
            if (!array_key_exists($beneficiaryQuestion, $choices)) {
                $form->get('autreQuestionSecrete')->setData($beneficiaryQuestion);
                $beneficiaryQuestion = end($choices);
            }

            $secretQuestionChoice->setData($beneficiaryQuestion);
        }
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $data = $event->getData();
        $secretQuestionChoice = $data['questionSecreteChoice'];

        $otherChoice = $this->translator->trans('membre.creationBeneficiaire.questionsSecretes.q9');
        $data['questionSecrete'] = $secretQuestionChoice && $otherChoice === $secretQuestionChoice
            ? $data['autreQuestionSecrete']
            : $secretQuestionChoice;

        $event->setData($data);
    }
}
