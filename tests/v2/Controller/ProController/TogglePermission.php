<?php

namespace App\Tests\v2\Controller\ProController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Attributes\Centre;
use App\Entity\MembreCentre;
use App\Entity\User;
use App\Repository\MembreCentreRepository;
use App\Tests\Factory\MembreFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class TogglePermission extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/pro/%s/relay/%s/toggle-permission/%s';
    private ?MembreCentreRepository $repository;

    protected function setUp(): void
    {
        $this->repository = self::getContainer()->get(MembreCentreRepository::class);
    }

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET', bool $isXmlHttpRequest = false, array $body = []): void
    {
        $randomPro = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();
        $authorizedPro = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER)->object();

        // Test with beneficiaryManagement
        $url = sprintf(self::URL, $randomPro->getId(), $authorizedPro->getAffiliatedRelaysWithProfessionalManagement()[0]->getId(), MembreCentre::MANAGE_BENEFICIARIES_PERMISSION);
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Test with proManagement
        $url = sprintf(self::URL, $randomPro->getId(), $authorizedPro->getAffiliatedRelaysWithProfessionalManagement()[0]->getId(), MembreCentre::MANAGE_PROS_PERMISSION);
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login', 'POST'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL, null, 'POST'];
        yield 'Should return 403 status code when authenticated as member with no permission' => [self::URL, 403, MemberFixture::MEMBER_MAIL_NO_RELAY_NO_PERMISSION, null, 'POST'];
        yield 'Should return 200 status code when authenticated as member with permissions' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER, null, 'POST'];
    }

    /** @dataProvider provideTestToggleBeneficiaryManagement */
    public function testShouldTogglePermissionOnClick(string $permission): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER)->object();
        $client->loginUser($user);

        // Request to pro list, with relay query param
        $relay = $user->getSubjectMembre()->getCentres()->first();
        $crawler = $client->request('GET', sprintf('/pro?relay=%s', $relay->getId()));

        // Fetch the first pro in list, check permissions
        $userInList = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();
        $userRelay = $userInList->getUserRelay($relay);
        $userPermissionBeforeUpdate = $userRelay->getDroits()[$permission];

        // Click on toggle permission button
        $form = $crawler->filter(sprintf('button#%s', $permission))->form();
        $client->submit($form);

        // Fetch updated MembreCentre
        $userRelay = $this->repository->find($userRelay->getId());
        $userPermissionOnAfterUpdate = $userRelay->getDroits()[$permission];

        // Assert permission has been toggled
        self::assertEquals(!$userPermissionBeforeUpdate, $userPermissionOnAfterUpdate);
    }

    public function provideTestToggleBeneficiaryManagement(): ?\Generator
    {
        yield 'Should toggle beneficiary management permission' => [MembreCentre::MANAGE_BENEFICIARIES_PERMISSION];
        yield 'Should toggle pro management permission' => [MembreCentre::MANAGE_PROS_PERMISSION];
    }

    public function testCanNotSeeNotOwnedPermission(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER)->object();
        $client->loginUser($user);

        // Test only for beneficiary management as pro list is unreachable without pro management permission
        $testedPermission = MembreCentre::MANAGE_BENEFICIARIES_PERMISSION;
        $ownedPermission = MembreCentre::MANAGE_PROS_PERMISSION;
        $relay = $this->getRelayToTestNotOwnedPermission($user, $testedPermission);

        // Request to pro list, with relay query param
        $crawler = $client->request('GET', sprintf('/pro?relay=%s', $relay->getId()));
        $ownedPermissionButtonCount = $crawler->filter(sprintf('button#%s', $ownedPermission))->count();
        $testedPermissionButtonCount = $crawler->filter(sprintf('button#%s', $testedPermission))->count();

        // Not owned permission button is not shown
        self::assertGreaterThan(0, $ownedPermissionButtonCount);
        self::assertEquals(0, $testedPermissionButtonCount);
    }

    public function testCanNotGiveNotOwnedPermission(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_MEMBER)->object();
        $client->loginUser($user);

        $testedPermission = MembreCentre::MANAGE_BENEFICIARIES_PERMISSION;
        $relay = $this->getRelayToTestNotOwnedPermission($user, $testedPermission);

        // Fetch pro from relay
        $firstUserInList = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();

        // Can not update permission
        $client->request('GET', sprintf(self::URL, $firstUserInList->getSubjectMembre()->getId(), $relay->getId(), $testedPermission));
        self::assertResponseStatusCodeSame(403);
    }

    private function getRelayToTestNotOwnedPermission(User $user, string $permission): Centre
    {
        $relay = $user->getSubjectMembre()->getCentres()->first();
        $userRelay = $user->getUserRelay($relay);

        $userRelay->removePermission($permission);
        $this->getEntityManager()->flush();

        return $relay;
    }
}
