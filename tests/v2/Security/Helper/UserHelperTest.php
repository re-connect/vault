<?php

namespace App\Tests\v2\Security\Helper;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\MembreCentre;
use App\Entity\Membre;
use App\Security\HelperV2\UserHelper;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserHelperTest extends KernelTestCase
{
    private ?UserHelper $userHelper;
    private Beneficiaire $beneficiary;
    private ?Membre $membre;

    protected function setUp(): void
    {
        $this->userHelper = $this->getContainer()->get(UserHelper::class);
        $this->beneficiary = BeneficiaireFactory::createOne()->object();
        $this->membre = MembreFactory::createOne()->object();
    }

    public function testCanManageBeneficiary(): void
    {
        // No relay common
        self::assertFalse($this->userHelper->canUpdateBeneficiary($this->membre->getUser(), $this->beneficiary));

        $relay = RelayFactory::createOne()->object();
        $this->beneficiary->addBeneficiaryRelayForRelay($relay);
        $this->membre->addMembresCentre((new MembreCentre())->setMembre($this->membre)->setCentre($relay));

        // Relay common, not accepted affiliation
        self::assertFalse($this->userHelper->canUpdateBeneficiary($this->membre->getUser(), $this->beneficiary));

        $this->membre->getMembresCentres()[0]->setBValid(true)->setDroits();

        // Relay common, accepted affiliation, no rights
        self::assertFalse($this->userHelper->canUpdateBeneficiary($this->membre->getUser(), $this->beneficiary));

        $this->membre->getMembresCentres()[0]->setDroits([MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => true]);

        // Relays common, accepted affiliation, has rights
        self::assertTrue($this->userHelper->canUpdateBeneficiary($this->membre->getUser(), $this->beneficiary));
    }
}
