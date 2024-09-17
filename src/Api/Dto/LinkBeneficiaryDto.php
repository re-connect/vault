<?php

namespace App\Api\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class LinkBeneficiaryDto
{
    #[Assert\NotNull]
    #[Groups(['v3:beneficiary:write'])]
    public string|int|null $distantId = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?int $externalCenter = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?int $externalProId = null;
}
