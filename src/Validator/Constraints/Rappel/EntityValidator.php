<?php

namespace App\Validator\Constraints\Rappel;

use App\Entity\Beneficiaire;
use App\Entity\Rappel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param Rappel $reminder
     *
     * @throws \Exception
     */
    #[\Override]
    public function validate(mixed $reminder, Constraint $constraint): void
    {
        if (!$reminder instanceof Rappel) {
            throw new UnexpectedTypeException($reminder, Rappel::class);
        }

        $currentDate = $reminder->getDate();

        if (!$currentDate instanceof \DateTime) {
            $reminder->setDate(new \DateTime($currentDate));
        }

        $this->checkBeneficiaryHasPhoneNumber($reminder);
        $this->preventUpdateAlreadySendReminder($reminder, $constraint);
        $this->checkNewReminderDatetime($reminder, $constraint);
    }

    private function checkBeneficiaryHasPhoneNumber(Rappel $reminder): void
    {
        $event = $reminder->getEvenement();
        $beneficiary = $event->getBeneficiaire();

        if (!$beneficiary && $event->beneficiaireId) {
            $beneficiary = $this->entityManager->getRepository(Beneficiaire::class)->find($event->beneficiaireId);
        }

        if (!$beneficiary?->getUser()?->getTelephone()) {
            $this->context->addViolation($this->translator->trans('no_phone_number_registered'));
        }
    }

    private function preventUpdateAlreadySendReminder(Rappel $reminder, Constraint $constraint): void
    {
        if ($reminder->getId()) {
            $reminderBeforeUpdate = $this->entityManager
                ->getUnitOfWork()
                ->getOriginalEntityData($reminder);
            $dateBeforeUpdate = $reminderBeforeUpdate['date'];
            $dateBeforeUpdateToUtc = new \DateTime($dateBeforeUpdate->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));

            if ($reminder->getBEnvoye() && $reminder->getDateToUtcTimezone() !== $dateBeforeUpdateToUtc) {
                $this->context->buildViolation($this->translator->trans('reminder_already_send'))
                    ->setParameter('{{ string }}', $dateBeforeUpdate->format('d/m/Y à H:i'))
                    ->addViolation();
            }
        }
    }

    private function checkNewReminderDatetime(Rappel $reminder, Constraint $constraint): void
    {
        $nowMinus12HoursUtc = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-12 hours -5 minutes');

        if (null === $reminder->getId() && $reminder->getDateToUtcTimezone() < $nowMinus12HoursUtc) {
            $this->context->addViolation($this->translator->trans('reminder_outdated'));
        }
    }
}
