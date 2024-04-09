<?php

namespace App\Validator\Constraints\Evenement;

use App\Entity\Evenement;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityValidator extends ConstraintValidator
{
    /**
     * @param Evenement $event
     *
     * @throws \Exception
     */
    public function validate(mixed $event, Constraint $constraint): void
    {
        if (!$event instanceof Evenement) {
            throw new UnexpectedTypeException($event, Evenement::class);
        }

        if (!$event->getDate() instanceof \DateTime) {
            $event->setDate(new \DateTime($event->getDate()));
        }

        $this->checkEventViolation($event, $constraint);
        $this->checkRemindersViolation($event, $constraint);
    }

    private function checkEventViolation(Evenement $event, Constraint $constraint): void
    {
        $nowMinus12HoursUtc = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-12 hours -5 minutes');

        if ($event->getDateToUtcTimezone() < $nowMinus12HoursUtc) {
            $this->context->buildViolation($constraint->messageRappelBeforeNow)
                ->atPath('date')
                ->addViolation();
        }
    }

    private function checkRemindersViolation(Evenement $event, Constraint $constraint): void
    {
        foreach ($event->getRappels() as $rappel) {
            if ($rappel->getDateToUtcTimezone() > $event->getDateToUtcTimezone()) {
                $this->context->addViolation($constraint->messageRappelAfterDateEvent);

                return;
            }
        }
    }
}
