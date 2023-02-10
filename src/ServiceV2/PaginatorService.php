<?php

namespace App\ServiceV2;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class PaginatorService
{
    private const LIMIT = 7;
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
    public function create(array $list, int $currentPage): PaginationInterface
    {
        return $this->paginator->paginate($list, $currentPage, self::LIMIT);
    }
}
