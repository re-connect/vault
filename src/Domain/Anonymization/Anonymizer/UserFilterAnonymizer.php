<?php

namespace App\Domain\Anonymization\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'user_filter',
    pack: 'reconnect',
    description: 'Anonymize users, exclude reconnect staff users and test users'
)]
class UserFilterAnonymizer extends AbstractAnonymizer
{
    public function anonymize(Update $update): void
    {
        $update->getWhere()
            ->isNotLike(ExpressionFactory::column('email', 'user'), '%@reconnect.fr')
            ->isEqual(ExpressionFactory::column('test', 'user'), 0);
    }
}
