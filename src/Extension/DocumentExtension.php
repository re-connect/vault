<?php

namespace App\Extension;

use App\Entity\Beneficiaire;
use App\Provider\DocumentProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DocumentExtension extends AbstractExtension
{
    private DocumentProvider $documentProvider;

    /**
     * DocumentExtension constructor.
     */
    public function __construct(DocumentProvider $documentProvider)
    {
        $this->documentProvider = $documentProvider;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getMaxSizeForBeneficiaire', [$this, 'getMaxSizeForBeneficiaire']),
            new TwigFunction('maxSizeSoonReached', [$this, 'maxSizeSoonReached']),
        ];
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('printFileSize', [$this, 'printFileSize']),
        ];
    }

    /**
     * @return mixed
     */
    public function getMaxSizeForBeneficiaire()
    {
        return $this->documentProvider->getMaxSizeForBeneficiaire();
    }

    /**
     * @return mixed
     */
    public function maxSizeSoonReached(Beneficiaire $beneficiaire)
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
