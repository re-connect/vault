<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\MemberFixture;
use App\Repository\BeneficiaireRepository;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

class BeneficiaryCreationDuplicateNotificationTest extends AbstractControllerTest
{
    private const URL = '/beneficiary/create/2/%s';
    private ?BeneficiaireRepository $repository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->repository = self::getContainer()->get(BeneficiaireRepository::class);
    }

    /** @dataProvider provideTestDuplicatedUsernameNotification */
    public function testDuplicatedUsernameNotification(string $url, int $expectedStatusCode, ?string $userMail = null): void
    {
        // We create first user
        $beneficiary = BeneficiaireFactory::createOne()->object();
        $user = $beneficiary->getUser();

        // We create user with duplicated username
        $duplicatedUser = UserFactory::createOne([
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
        ]);
        $duplicatedBeneficiary = BeneficiaireFactory::createOne(['user' => $duplicatedUser, 'dateNaissance' => $beneficiary->getDateNaissance()]);
        $creationProcessWithDuplicatedBeneficiary = BeneficiaryCreationProcessFactory::createOne(['beneficiary' => $duplicatedBeneficiary])->object();

        // Request to step 2
        $url = sprintf($url, $creationProcessWithDuplicatedBeneficiary->getId());
        $client = $this->assertRoute($url, $expectedStatusCode, $userMail);

        // Check duplicate username notification exists
        $duplicatedBeneficiaryId = $duplicatedBeneficiary->getId();
        $crawler = $client->getCrawler();
        $modalText = $crawler->filter('.modal-dialog')->html();
        self::assertTrue(str_contains($modalText, self::$translator->trans('duplicated_username')));
        self::assertTrue(str_contains($modalText, self::$translator->trans('duplicated_username_alert')));

        // Abort creation
        $uri = $crawler->filter('.modal-footer > div > a.btn-primary ')->link()->getUri();
        $client->request('GET', $uri);
        self::assertNull($this->repository->find($duplicatedBeneficiaryId));
    }

    public function provideTestDuplicatedUsernameNotification(): ?\Generator
    {
        yield 'Should display duplicated username notification' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
    }
}
