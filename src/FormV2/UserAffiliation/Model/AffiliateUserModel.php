<?php

namespace App\FormV2\UserAffiliation\Model;

use App\Entity\Centre;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ReadableCollection;

class AffiliateUserModel
{
    /** @var ReadableCollection<int, Centre> */
    public ReadableCollection $relays;

    /** @param ?ReadableCollection<int, Centre> $relays */
    public function __construct(?ReadableCollection $relays = null)
    {
        $this->relays = $relays ?: new ArrayCollection();
    }
}
