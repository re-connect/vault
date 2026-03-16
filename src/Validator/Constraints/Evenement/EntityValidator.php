<?php

namespace App\Validator\Constraints\Evenement;

use App\Entity\Evenement;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityValidator extends ConstraintValidator
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param Evenement $event
     *
     * @throws \Exception
     */
    #[\Override]
    public function validate(mixed $event, Constraint $constraint): void
    {
        if (!$event instanceof Evenement) {
            throw new UnexpectedTypeException($event, Evenement::class);
        }

        if (!$event->getDate() instanceof \DateTime) {
            $event->setDate(new \DateTime($event->getDate()));
        }

        $this->checkEventViolation($event);
        $this->checkRemindersViolation($event);
    }

    private function checkEventViolation(Evenement $event): void
    {
        $nowMinus12HoursUtc = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-12 hours -5 minutes');

        if ($event->getDateToUtcTimezone() < $nowMinus12HoursUtc) {
            $this->context->buildViolation($this->translator->trans('event_outdated'))
                ->atPath('date')
                ->addViolation();
        }
    }

    private function checkRemindersViolation(Evenement $event): void
    {
        foreach ($event->getRappels() as $rappel) {
            if ($rappel->getDateToUtcTimezone() > $event->getDateToUtcTimezone()) {
                $this->context->addViolation($this->translator->trans('reminder_should_not_outdate_event'));

                return;
            }
        }
    }
}
