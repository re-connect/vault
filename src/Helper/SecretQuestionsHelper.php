<?php

namespace App\Helper;

use App\Entity\Beneficiaire;
use App\Form\Event\SecretQuestionListener;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecretQuestionsHelper
{
    use UserAwareTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
    ) {
    }

    /** @return array<string, string> */
    public function getSecretQuestions(): array
    {
        $secretQuestions = [];
        foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
            $secretQuestions[$this->translator->trans($key)] = $this->translator->trans($value);
        }

        return $secretQuestions;
    }

    /** @param array<string, string> $secretQuestions */
    public function getSecretQuestionDefaultValue(Beneficiaire $beneficiary, array $secretQuestions): string
    {
        if ($beneficiarySecretQuestion = $beneficiary->getQuestionSecrete()) {
            return array_key_exists($beneficiarySecretQuestion, $secretQuestions)
                ? $beneficiarySecretQuestion
                : $secretQuestions[$this->translator->trans('membre.creationBeneficiaire.questionsSecretes.q9')];
        }

        return array_key_first($secretQuestions);
    }

    public function createSecretQuestionListener(): SecretQuestionListener
    {
        return new SecretQuestionListener($this->translator);
    }

    public function beneficiaryMissesSecretQuestion(?Beneficiaire $beneficiary): bool
    {
        return $beneficiary && !$beneficiary->getQuestionSecrete();
    }

    public function getCurrentBeneficiary(): ?Beneficiaire
    {
        return $this->getUser()?->getSubjectBeneficiaire();
    }
}
