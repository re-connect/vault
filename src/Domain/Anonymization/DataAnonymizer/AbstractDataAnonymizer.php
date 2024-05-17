<?php

namespace App\Domain\Anonymization\DataAnonymizer;

use Doctrine\ORM\EntityManagerInterface;

abstract readonly class AbstractDataAnonymizer
{
    final public const string BASE_UPDATE_QUERY = 'UPDATE %s d SET %s %s';

    public function __construct(protected EntityManagerInterface $em)
    {
    }

    protected function executeQuery($dql): void
    {
        $this->em->createQuery($dql)->execute();
    }

    protected function createQuery(string $className, string $columnsUpdate, ?string $whereStatement = null): string
    {
        return sprintf(self::BASE_UPDATE_QUERY, $className, $columnsUpdate, $whereStatement);
    }
}
