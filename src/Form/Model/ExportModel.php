<?php

namespace App\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ExportModel
{
    private Collection $centers;
    private Collection $regions;
    private \DateTime $startDate;
    private \DateTime $endDate;

    public function __construct()
    {
        $this->centers = new ArrayCollection();
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
    }

    public function getCenters(): Collection
    {
        return $this->centers;
    }

    public function setCenters(Collection $centers): void
    {
        $this->centers = $centers;
    }

    public function getRegions(): Collection
    {
        return $this->regions;
    }

    public function setRegions(Collection $regions): void
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

    public function getFiltersCollection(): Collection
    {
        return $this->hasCenterFilter() ? $this->getCenters() : $this->getRegions();
    }
}
