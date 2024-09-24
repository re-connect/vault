<?php

namespace App\Checker;

use App\Repository\FeatureFlagRepository;
use Doctrine\ORM\EntityManagerInterface;

class FeatureFlagChecker
{
    public function __construct(
        private readonly FeatureFlagRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function isEnabled(string $featureName): bool
    {
        $featureFlag = $this->repository->findOneBy(['name' => $featureName]);
        if (!$featureFlag?->isEnabled() && $featureFlag?->isEnableDateDue()) {
            $this->enable($featureFlag->getName());
        }

        return $featureFlag?->isEnabled() ?: false;
    }

    public function getEnableDate(string $featureName): ?\DateTimeImmutable
    {
        $featureFlag = $this->repository->findOneBy(['name' => $featureName]);

        return $featureFlag?->getEnableDate();
    }

    public function enable(string $featureName): void
    {
        $featureFlag = $this->repository->findOneBy(['name' => $featureName]);
        $featureFlag?->enable();

        $this->em->flush();
    }

    public function disable(string $featureName): void
    {
        $featureFlag = $this->repository->findOneBy(['name' => $featureName]);
        $featureFlag?->disable();

        $this->em->flush();
    }
}
