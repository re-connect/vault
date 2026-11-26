<?php

namespace App\Tests\v2\Security\Helper;

use App\Entity\MembreCentre;
use App\Security\HelperV2\UserHelper;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UserHelperBeneficiaryBeingCreatedTest extends KernelTestCase
{
    use Factories;

    public function testCanNotManageBeneficiaryBeingCreated(): void
    {
        $userHelper = $this->getContainer()->get(UserHelper::class);
        $beneficiary = BeneficiaireFactory::createOne();
        $membre = MembreFactory::createOne();
        $relay = RelayFactory::createOne();
        $creationProcess = BeneficiaryCreationProcessFactory::createOne(['beneficiary' => $beneficiary, 'isCreating' => false]);
        $beneficiary->addBeneficiaryRelayForRelay($relay);
        $membre->addMembresCentre((new MembreCentre())->setMembre($membre)->setCentre($relay)->setBValid(true)->setDroits([MembreCentre::MANAGE_BENEFICIARIES_PERMISSION => true]));
        self::assertTrue($userHelper->canUpdateBeneficiary($membre->getUser(), $beneficiary));

        $creationProcess->setIsCreating(true);

        self::assertFalse($userHelper->canUpdateBeneficiary($membre->getUser(), $beneficiary));
    }
}
