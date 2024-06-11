<?php

namespace App\Api\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class LinkBeneficiaryDto
{
    #[Assert\NotNull()]
    #[Groups(['v3:beneficiary:write'])]
    public ?string $distantId = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $externalCenter = null;

    #[Groups(['v3:beneficiary:write'])]
    public ?string $externalProId = null;
}
