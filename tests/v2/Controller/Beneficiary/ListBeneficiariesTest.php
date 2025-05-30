<?php

namespace App\Tests\v2\Controller\Beneficiary;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Attributes\Centre;
use App\Repository\BeneficiaireRepository;
use App\Security\HelperV2\UserHelper;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;
use Zenstruck\Foundry\Proxy;

class ListBeneficiariesTest extends AbstractControllerTest implements TestRouteInterface
{
    private ?UserHelper $userHelper;
    private ?BeneficiaireRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();
        $this->userHelper = $container->get(UserHelper::class);
        $this->repository = $container->get(BeneficiaireRepository::class);
    }

    private const URL = '/beneficiaries';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as member' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function testPermissionOnBeneficiaries(): void
    {
        $proUser = $this->getTestUserFromDb(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES);
        $beneficiaries = $this->repository->findByAuthorizedProfessional($proUser->getSubject());

        // We check that all fetched beneficiaries can be managed by the professional
        foreach ($beneficiaries as $beneficiary) {
            // We need to fetch each beneficiaries with factory, because findByAuthorizedProfessional does not hydrate properties such as $beneficiaireCentre
            $beneficiary = BeneficiaireFactory::find($beneficiary->getId())->object();
            self::assertTrue($this->userHelper->canUpdateBeneficiary($proUser, $beneficiary));
        }
    }

    public function testCanNotFilterOnUnauthorizedRelays(): void
    {
        $userMail = MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES;
        $user = UserFactory::findByEmail($userMail)->object();

        $allRelaysIds = array_map(fn (Proxy $relay) => $relay->object()->getId(), RelayFactory::all());
        $allUserRelaysIds = array_map(fn (Centre $relay) => $relay->getId(), $user->getAffiliatedRelaysWithBeneficiaryManagement()->toArray());
        $notAffiliatedRelaysIds = array_diff($allRelaysIds, $allUserRelaysIds);

        array_map(
            fn (int $relayId) => $this->assertRoute(
                sprintf('%s?relay=%d', self::URL, $relayId),
                403,
                $userMail,
            ),
            $notAffiliatedRelaysIds,
        );
    }
}
