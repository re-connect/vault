<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateTest extends AbstractControllerTest
{
    private const URL = '/beneficiary/%s/documents/upload';

    public function provideTestUploadRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null];
        yield 'Should return 201 status code when authenticated as beneficiaire' => [self::URL, 201, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 201 status code when authenticated as member with relay in common' => [self::URL, 201, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should redirect when authenticated as an other beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS, '/beneficiary/home'];
        yield 'Should redirect when authenticated as member with no relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL, '/professional/beneficiaries'];
    }

    /** @dataProvider provideTestUploadRoute */
    public function testUpload(string $url, int $expectedStatusCode, ?string $userMail = null): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $url = sprintf($url, $beneficiary->getId());
        $file = new UploadedFile('tests/test-file.pdf', 'test', 'application/pdf');

        if ($userMail) {
            $user = $this->getTestUserFromDb($userMail);
            $client->loginUser($user);
        }

        $client->request('POST', $url, [], ['files' => [$file]]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }
}
