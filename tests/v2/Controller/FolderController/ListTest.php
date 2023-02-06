<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class ListTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = '/folder/%s';

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $folder = FolderFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object(),
        ])->object();
        $url = sprintf($url, $folder->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
    }
}
