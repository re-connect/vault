<?php

namespace App\EventSubscriber;

use App\EventV2\BeneficiaryConsultationEvent;
use App\ManagerV2\MemberBeneficiaryManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class BeneficiaryConsultationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MemberBeneficiaryManager $memberBeneficiaryManager,
    ) {
    }

    public function recordBeneficiaryConsultation(BeneficiaryConsultationEvent $event): void
    {
        $this->memberBeneficiaryManager->recordBeneficiaryConsultation($event->getBeneficiary());
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            BeneficiaryConsultationEvent::class => 'recordBeneficiaryConsultation',
        ];
    }
}
