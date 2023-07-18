<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController\Remotely;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\Factory\CreatorCentreFactory;
use App\Tests\Factory\CreatorUserFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationStep4Test extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/beneficiary/create/4/%s';

    /** @dataProvider provideTestRoute */
    public function testRoute(
        string $url,
        int $expectedStatusCode,
        string $userMail = null,
        string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): void {
        // We create beneficiary and creation process for each test
        $user = UserFactory::createOne([
            'creators' => [
                CreatorCentreFactory::createOne(),
                CreatorUserFactory::createOne(),
            ], ])->object();
        $beneficiary = BeneficiaireFactory::createOne(['user' => $user])->object();

        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate([
            'isCreating' => true,
            'remotely' => true,
            'beneficiary' => $beneficiary,
        ])->object();

        $url = sprintf($url, $creationProcess->getId());
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $creationProcess->getId()) : null;
        $client = $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        $client->request('GET', sprintf('/beneficiary/create/4/%s', $creationProcess->getId()));

        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS === $userMail) {
            self::assertFalse(BeneficiaryCreationProcessFactory::find($creationProcess)->object()->getIsCreating());
        }
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS, '/beneficiary/create/download-terms-of-use/%s'];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }
}
