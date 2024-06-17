<?php

namespace App\Domain\Anonymization\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'user_filter',
    pack: 'reconnect',
    description: 'Anonymize users, exclude Reconnect staff users and test users'
)]
class UserFilterAnonymizer extends AbstractAnonymizer
{
    #[\Override]
    public function anonymize(Update $update): void
    {
        $update->getWhere()->raw("user.test IS FALSE AND (user.email NOT LIKE '%@reconnect.fr' OR user.email IS NULL)");
    }
}
