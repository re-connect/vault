<?php

namespace App\Tests\v2\Controller\SharedDocument;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestRouteInterface;

class ShareWithContactTest extends AbstractControllerTest implements TestRouteInterface
{
    private const URL = 'document/%s/share-with-contact/%s';

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should redirect after email send when authenticated as beneficiaire' => [self::URL, 302, BeneficiaryFixture::BENEFICIARY_MAIL, '/beneficiary/%s/documents'];
        yield 'Should redirect after email send when authenticated as member with relay in common' => [self::URL, 302, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES, '/beneficiary/%s/documents'];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $document = DocumentFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();
        $contact = ContactFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false, 'email' => 'test@yopmail.com'])->object();

        $url = sprintf($url, $document->getId(), $contact->getId());
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    /**
     * @dataProvider provideTestSendEmailToContact
     */
    public function testSendEmailToContact(string $email): void
    {
        $clientTest = static::createClient();
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $clientTest->loginUser($beneficiary->getUser());

        $document = DocumentFactory::findOrCreate(['beneficiaire' => $beneficiary])->object();
        $contact = ContactFactory::findOrCreate(['beneficiaire' => $beneficiary, 'email' => $email])->object();

        $clientTest->request('GET', sprintf(self::URL, $document->getId(), $contact->getId()));
        $this->assertEmailCount($email ? 1 : 0);
    }

    public function provideTestSendEmailToContact(): \Generator
    {
        yield 'Should send mail for contact with email' => ['test@yopmail.com'];
        yield 'Should not send mail for contact with no email' => [''];
    }
}
