<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\RepositoryV2\BeneficiaryCreationProcessRepository;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class AbortCreationTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/create/abort/%s';
    private BeneficiaryCreationProcessRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->repository = self::createClient()->getContainer()->get(BeneficiaryCreationProcessRepository::class);
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
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        if (MemberFixture::MEMBER_MAIL === $userMail) {
            self::assertNull($this->repository->find($creationProcess->getId()));
        }
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect to home when authenticated as professional' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/membre/beneficiaires/'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }
}
