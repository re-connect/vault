<?php

namespace App\Entity\Helper;

use App\Entity\Beneficiaire;
use Symfony\Component\HttpFoundation\Response;

readonly class BeneficiaryCheckOnRosalie
{
    public function __construct(private Beneficiaire $beneficiary, private ?int $responseStatusCode = null, private ?int $similarityPercentage = null)
    {
    }

    public static function fromResponse(Beneficiaire $beneficiary, ?int $statusCode = null, ?string $payload = null): self
    {
        $responseData = json_decode($payload, true);
        $similarity = $responseData['similarity'] ?? null;

        return new BeneficiaryCheckOnRosalie($beneficiary, $statusCode, self::computeSimilarityPercentage($similarity));
    }

    private static function computeSimilarityPercentage(?float $similarity): ?int
    {
        return $similarity ? floor($similarity * 100) : null;
    }

    public function getBeneficiary(): Beneficiaire
    {
        return $this->beneficiary;
    }

    public function beneficiaryIsFound(): bool
    {
        return Response::HTTP_OK === $this->responseStatusCode && 100 === $this->similarityPercentage;
    }

    private function beneficiaryIsAlmostFound(): bool
    {
        return Response::HTTP_OK === $this->responseStatusCode && 100 > $this->similarityPercentage;
    }

    private function sisiaoNumberDoesNotMatchBeneficiary(): bool
    {
        return Response::HTTP_BAD_REQUEST === $this->responseStatusCode;
    }

    private function sisiaoNumberDoesNotExist(): bool
    {
        return Response::HTTP_BAD_REQUEST < $this->responseStatusCode;
    }

    public function getSamuSocialErrorMessage(): string
    {
        return match (true) {
            $this->beneficiaryIsAlmostFound() => 'si_siao_number_almost_found_rosalie',
            $this->sisiaoNumberDoesNotMatchBeneficiary() => 'si_siao_number_does_not_match_rosalie',
            $this->sisiaoNumberDoesNotExist() => 'si_siao_number_not_found_rosalie',
            default => 'error',
        };
    }
}
