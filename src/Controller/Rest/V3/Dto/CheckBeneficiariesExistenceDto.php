<?php

namespace App\Controller\Rest\V3\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class CheckBeneficiariesExistenceDto
{
    public const int MAX_IDS = 1000;

    /**
     * @param array<int|string> $distantIds
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1, max: self::MAX_IDS)]
        #[Assert\All([new Assert\Type(['string', 'int']), new Assert\NotBlank()])]
        #[SerializedName('distant_ids')]
        public array $distantIds = [],
    ) {
    }

    /**
     * @return string[] distant ids cast to strings, deduplicated, in the order they were sent
     */
    public function getNormalizedDistantIds(): array
    {
        return array_values(array_unique(array_map(strval(...), $this->distantIds)));
    }
}
