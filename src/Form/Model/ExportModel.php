<?php

namespace App\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ExportModel
{
    private Collection $centers;
    private array $regions;
    private \DateTime $startDate;
    private \DateTime $endDate;

    public function __construct()
    {
        $this->centers = new ArrayCollection();
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
    }

    public function getCenters()
    {
        return $this->centers;
    }

    public function setCenters($centers): void
    {
        $this->centers = $centers;
    }

    public function getRegions(): array
    {
        return $this->regions;
    }

    public function setRegions(array $regions): void
    {
        $this->regions = $regions;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function hasCenterFilter(): bool
    {
        return count($this->centers) > 0;
    }

    public function hasRegionFilter(): bool
    {
        return count($this->regions) > 0;
    }

    public function hasFilters(): bool
    {
        return $this->hasCenterFilter() || $this->hasRegionFilter();
    }

    public function getFiltersCollection(): ArrayCollection
    {
        return $this->hasCenterFilter() ? $this->getCenters() : new ArrayCollection($this->getRegions());
    }
}
