<?php

namespace App\ServiceV2;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class PaginatorService
{
    private const LIMIT = 7;
    public const LIST_USER_LIMIT = 10;
    private PaginatorInterface $paginator;

    public function __construct(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
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
