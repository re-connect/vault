<?php

namespace App\FormV2\Search;

class SearchFormModel
{
    public function __construct(private ?string $search = '')
    {
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): self
    {
        $this->search = $search;

        return $this;
    }
}
