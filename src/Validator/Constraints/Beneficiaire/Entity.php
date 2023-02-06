<?php

namespace App\Validator\Constraints\Beneficiaire;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Entity extends Constraint
{
    public $message = '';
    public $messageDateNaissance = 'La date de naissance doit être au format YYYY-mm-dd.';
    public $messageDuplicateCentreBeneficaireExternalLink = 'La liaison externe "{{ string }}" est déjà lié à un centre.';
    public $messageDuplicateBeneficiaireCentre = 'Le centre "{{ string }}" est déjà lié au bénéficiaire.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
