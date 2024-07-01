<?php

namespace App\Domain\Anonymization\Anonymizer;

use App\Domain\Anonymization\AnonymizationHelper;
use DbToolsBundle\PackFrFR\Anonymizer\LastNameAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'personal_data_name',
    pack: 'reconnect',
    description: 'Anonymize personal data name according to child entity'
)]
class PersonalDataNameAnonymizer extends LastNameAnonymizer
{
    #[\Override]
    protected function getSample(): array
    {
        return match ($this->tableName) {
            'contact' => parent::getSample(),
            'document' => [AnonymizationHelper::ANONYMIZED_DOCUMENT],
            default => [AnonymizationHelper::ANONYMIZED_SUBJECT],
        };
    }
}
