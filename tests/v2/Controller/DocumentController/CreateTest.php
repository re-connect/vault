<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
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
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    /** @dataProvider provideTestUploadRoute */
    public function testUpload(string $url, int $expectedStatusCode, ?string $userMail = null): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $url = sprintf($url, $beneficiary->getId());
        $file = new UploadedFile('tests/test-file.pdf', 'test', 'application/pdf');

        if ($userMail) {
            $user = $this->getTestUserFromDb($userMail);
            $client->loginUser($user);
        }

        $client->request('POST', $url, [], ['files' => [$file]]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function testUploadWhenNameIsTooLong(): void
    {
        self::ensureKernelShutdown();
        $client = $this->createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $documentCount = DocumentFactory::count(['beneficiaire' => $beneficiary]);
        $client->loginUser($this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL));
        $tooLongFileName = str_repeat('a', Document::NAME_MAX_LENGTH + 1).'.pdf';
        $file = new UploadedFile('tests/test-file.pdf', $tooLongFileName, 'application/pdf');

        $client->request('POST', '/beneficiary/'.$beneficiary->getId().'/documents/upload', [], ['files' => [$file]]);

        $this->assertResponseStatusCodeSame(201);
        $expectedMessage = self::$translator->trans('document_name_too_long', ['{{ limit }}' => Document::NAME_MAX_LENGTH], 'validators');
        self::assertContains($expectedMessage, $client->getRequest()->getSession()->getFlashBag()->peek('danger'));
        self::assertSame($documentCount, DocumentFactory::count(['beneficiaire' => $beneficiary]));
    }
}
