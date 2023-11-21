<?php

namespace App\Tests\v2\Listener\Logs;

use App\Entity\BeneficiaireCentre;
use App\Entity\MembreCentre;
use App\Entity\UserCentre;
use App\ListenerV2\Logs\AffiliationListener;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\RelayFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class AffiliationListenerTest extends AbstractLogActivityListenerTest
{
    use Factories;

    private const LOG_FILE_NAME = 'affiliation.log';
    private ?AffiliationListener $affiliationListener;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->affiliationListener = $this->getContainer()->get(AffiliationListener::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function testPostPersistBeneficiaireCentre(): void
    {
        $beneficiaireCentre = $this->createBeneficiaireCentre();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Affiliation link created', ...$this->getLogContent($beneficiaireCentre)]);
    }

    public function testPreUpdateBeneficiaireCentre(): void
    {
        $beneficiaireCentre = $this->createBeneficiaireCentre();
        $beneficiaireCentre->setBValid(true);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Affiliation link accepted', ...$this->getLogContent($beneficiaireCentre)]);
    }

    public function testPreRemoveBeneficiaireCentre(): void
    {
        $beneficiaireCentre = $this->createBeneficiaireCentre();
        $logContent = $this->getLogContent($beneficiaireCentre);
        $this->em->remove($beneficiaireCentre);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Affiliation link deleted', ...$logContent]);
    }

    public function testPostPersistMembreCentre(): void
    {
        $membreCentre = $this->createMembreCentre();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Affiliation link created', ...$this->getLogContent($membreCentre)]);
    }

    public function testPreUpdateMembreCentre(): void
    {
        $membreCentre = $this->createMembreCentre();
        $membreCentre->setBValid(true);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Affiliation link accepted', ...$this->getLogContent($membreCentre)]);
    }

    public function testPreRemoveMembreCentre(): void
    {
        $membreCentre = $this->createMembreCentre();
        $logContent = $this->getLogContent($membreCentre);
        $this->em->remove($membreCentre);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Affiliation link deleted', ...$logContent]);
    }

    private function getLogContent(UserCentre $userCentre): array
    {
        return [
            'entity_id' => $userCentre->getId(),
            'relay' => $userCentre->getCentre()?->getId(),
            'user' => $userCentre->getUser()?->getId(),
            'accepted' => $userCentre->getBValid(),
            'by_user_id' => $this->loggedUser?->getId(),
        ];
    }

    private function createBeneficiaireCentre(): BeneficiaireCentre
    {
        $beneficiaireCentre = (new BeneficiaireCentre())
                ->setCentre(RelayFactory::createOne()
                ->object())->setBeneficiaire(BeneficiaireFactory::createOne()->object())
                ->setBValid(false);

        $this->em->persist($beneficiaireCentre);
        $this->em->flush();

        return $beneficiaireCentre;
    }

    private function createMembreCentre(): MembreCentre
    {
        $membreCentre = (new MembreCentre())->setCentre(RelayFactory::createOne()->object())->setMembre(MembreFactory::createOne()->object());

        $this->em->persist($membreCentre);
        $this->em->flush();

        return $membreCentre;
    }
}
