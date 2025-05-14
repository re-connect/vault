<?php

namespace App\Extension;

use App\Entity\Attributes\Beneficiaire;
use App\Provider\DocumentProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DocumentExtension extends AbstractExtension
{
    /**
     * DocumentExtension constructor.
     */
    public function __construct(private readonly DocumentProvider $documentProvider)
    {
    }

    /**
     * @return array|TwigFunction[]
     */
    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('getMaxSizeForBeneficiaire', $this->getMaxSizeForBeneficiaire(...)),
            new TwigFunction('maxSizeSoonReached', $this->maxSizeSoonReached(...)),
        ];
    }

    /**
     * @return array|TwigFilter[]
     */
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('printFileSize', $this->printFileSize(...)),
        ];
    }

    public function getMaxSizeForBeneficiaire(): int
    {
        return $this->documentProvider->getMaxSizeForBeneficiaire();
    }

    public function maxSizeSoonReached(Beneficiaire $beneficiaire): bool
    {
        return $this->documentProvider->maxSizeSoonReached($beneficiaire);
    }

    public function printFileSize($fileSize): string
    {
        if ($fileSize / 1024 > 1024) {
            return number_format($fileSize / (1024 * 1024), 2).'mo';
        }

        if ($fileSize > 1024) {
            return number_format($fileSize / 1024, 0).'ko';
        }

        return $fileSize.'o';
    }

    public function getName(): string
    {
        return 'DocumentExtension';
    }
}
