<?php

namespace App\Tests\v2\Controller\DocumentController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Document;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;
use Doctrine\ORM\EntityManagerInterface;

class RenameTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/document/%s/rename';
    private const FORM_VALUES = [
        'rename_document[nom]' => 'new name',
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = DocumentFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false]);

        $url = sprintf($url, $document->getId());
        $expectedRedirect = $expectedRedirect ? sprintf($expectedRedirect, $beneficiary->getId()) : '';
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $newDocument = DocumentFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true]);
            $newUrl = sprintf(self::URL, $newDocument->getId());
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $document = $beneficiary->getDocuments()[0];
        $url = sprintf($url, $document->getId());
        $redirectUrl = $document->hasParentFolder() ? sprintf('/folder/%s', $document->getDossier()->getId()) : sprintf('/beneficiary/%s/documents', $beneficiary->getId());
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should refresh when valid' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL,
            null,
        ];

        yield 'Should refresh when name has the maximum length' => [
            self::URL,
            'confirm',
            ['rename_document[nom]' => str_repeat('a', Document::NAME_MAX_LENGTH)],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            null,
        ];
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL);
        $url = sprintf($url, $beneficiary->getDocuments()[0]->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['rename_document[nom]'] = '';

        yield 'Should return an error when name is empty' => [
            self::URL,
            'document_rename',
            'confirm',
            $values,
            [
                [
                    'message' => 'This value should not be blank.',
                    'params' => null,
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            'div.invalid-feedback',
        ];

        yield 'Should return an error when name is too long' => [
            self::URL,
            'document_rename',
            'confirm',
            ['rename_document[nom]' => str_repeat('a', Document::NAME_MAX_LENGTH + 1)],
            [
                [
                    'message' => 'document_name_too_long',
                    'params' => ['{{ limit }}' => (string) Document::NAME_MAX_LENGTH],
                ],
            ],
            BeneficiaryFixture::BENEFICIARY_MAIL,
            'div.invalid-feedback',
        ];
    }

    /** @dataProvider provideNamesWithAndWithoutExtension */
    public function testFormHidesFileExtension(string $submittedName, string $expectedSavedName): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($this->getTestUserFromDb(BeneficiaryFixture::BENEFICIARY_MAIL));
        $document = DocumentFactory::createOne([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL),
            'nom' => 'facture_recette.pdf',
            'extension' => 'pdf',
        ]);
        $documentId = $document->getId();

        $crawler = $client->request('GET', '/document/'.$documentId.'/rename');
        self::assertSame('facture_recette', $crawler->filter('input[name="rename_document[nom]"]')->attr('value'));

        $form = $crawler->selectButton(self::$translator->trans('confirm'))->form();
        $client->submit($form, ['rename_document[nom]' => $submittedName]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertSame($expectedSavedName, $em->find(Document::class, $documentId)->getNom());
    }

    public static function provideNamesWithAndWithoutExtension(): \Generator
    {
        yield 'Extension is added back when the name has none' => ['nouveau nom', 'nouveau_nom.pdf'];
        yield 'Extension is not doubled when the user types it' => ['nouveau.pdf', 'nouveau.pdf'];
    }
}
