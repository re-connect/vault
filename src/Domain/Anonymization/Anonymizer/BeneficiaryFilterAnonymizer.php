<?php

namespace App\Domain\Anonymization\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'beneficiary_filter',
    pack: 'reconnect',
    description: 'Anonymize beneficiaries, exclude Reconnect staff users and test users'
)]
class BeneficiaryFilterAnonymizer extends AbstractAnonymizer
{
    #[\Override]
    public function anonymize(Update $update): void
    {
        $update
            ->join('user', 'beneficiaire.user_id = user.id')
            ->getWhere()->raw(UserFilterAnonymizer::WHERE_CLAUSE_FILTER);
    }
}
