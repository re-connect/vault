<?php

namespace App\Tests\v2\Manager\FolderManager;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\ManagerV2\FolderManager;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;

class getRootFoldersTest extends AuthenticatedKernelTestCase
{
    public function testBeneficiaryShouldGetAllRootFolders()
    {
        self::ensureKernelShutdown();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $this->loginUser(BeneficiaryFixture::BENEFICIARY_MAIL);
        $manager = static::getContainer()->get(FolderManager::class);
        $this->assertEquals($beneficiary->getRootFolders(), $manager->getRootFolders($beneficiary));
    }

    public function testMemberShouldGetSharedRootFoldersOnly()
    {
        self::ensureKernelShutdown();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        // Ensure beneficiary has at least 1 private folder for test purpose
        FolderFactory::createOne(['beneficiaire' => $beneficiary, 'bPrive' => true]);
        $this->loginUser(MemberFixture::MEMBER_MAIL);
        $manager = static::getContainer()->get(FolderManager::class);
        $this->assertNotEquals($beneficiary->getRootFolders(), $manager->getRootFolders($beneficiary));
        $this->assertEquals($beneficiary->getSharedRootFolders(), $manager->getRootFolders($beneficiary));
    }
}
