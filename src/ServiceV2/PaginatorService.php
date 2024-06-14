<?php

namespace App\ServiceV2;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class PaginatorService
{
    private const int LIMIT = 7;
    public const LIST_USER_LIMIT = 10;

    public function __construct(private readonly PaginatorInterface $paginator)
    {
    }

    /**
     * @param object[] $list
     *
     * @return PaginationInterface<int, object>
     */
    public function create(array $list, int $currentPage, int $limit = self::LIMIT): PaginationInterface
    {
        return $this->paginator->paginate($list, $currentPage, $limit);
    }
}
