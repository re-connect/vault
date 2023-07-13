<?php

namespace App\Tests\v2\Controller\FolderController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class CreateSubFolderTest extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/folder/%s/create-subfolder';
    private const FORM_VALUES = [
        'folder[nom]' => 'Emploi',
    ];

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as beneficiaire' => [self::URL, 200, BeneficiaryFixture::BENEFICIARY_MAIL];
        yield 'Should return 200 status code when authenticated as member with relay in common' => [self::URL, 200, MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES];
        yield 'Should return 403 status code when authenticated as an other beneficiaire' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS];
        yield 'Should return 403 status code when authenticated as member with no relay in common' => [self::URL, 403, MemberFixture::MEMBER_MAIL];
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should refresh when form is correct' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            BeneficiaryFixture::BENEFICIARY_MAIL,
            '/folder/%s',
        ];
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['folder[nom]'] = '';

        yield 'Should return an error when nom is empty' => [
            self::URL,
            'folder_create_subfolder',
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
    }

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
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object();
        $publicFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => false])->object();

        $url = sprintf($url, $publicFolder->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);

        // Also check that authorized Pro can't update private data
        if (MemberFixture::MEMBER_MAIL_WITH_RELAYS_SHARED_WITH_BENEFICIARIES === $userMail) {
            $privateFolder = FolderFactory::findOrCreate(['beneficiaire' => $beneficiary, 'bPrive' => true])->object();
            $newUrl = sprintf(self::URL, $privateFolder->getId());
            $this->assertRoute($newUrl, 403, $userMail, null, $method, true);
        }
    }

    /**  @dataProvider provideTestFormIsValid */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $parentFolder = FolderFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object(),
        ])->object();
        $url = sprintf($url, $parentFolder->getId());
        $redirectUrl = $redirectUrl ? sprintf($redirectUrl, $parentFolder->getId()) : '';
        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);

        $subFolder = $parentFolder->getSousDossiers()[0]->getId();
        FolderFactory::find($subFolder)->remove();
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, string $alternateSelector = null): void
    {
        $folder = FolderFactory::findOrCreate([
            'beneficiaire' => BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object(),
        ])->object();
        $url = sprintf($url, $folder->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function testCreateSubfolder(): void
    {
        self::ensureKernelShutdown();
        $clientTest = static::createClient();
        $user = UserFactory::find(['email' => BeneficiaryFixture::BENEFICIARY_MAIL])->object();
        $clientTest->loginUser($user);

        $beneficiary = $user->getSubjectBeneficiaire();
        $parentFolder = FolderFactory::createOne(['beneficiaire' => $beneficiary])->object();

        $crawler = $clientTest->request('GET', sprintf(self::URL, $parentFolder->getId()));
        $form = $crawler->selectButton(self::$translator->trans('confirm'))->form();
        $form->setValues(self::FORM_VALUES);
        $clientTest->submit($form);

        $parentFolder = FolderFactory::find(['id' => $parentFolder->getId()])->object();
        $subFolder = $parentFolder->getSousDossiers()[0];

        self::assertCount(1, $parentFolder->getSousDossiers());
        self::assertSame($parentFolder, $subFolder->getDossierParent());
        self::assertEquals($parentFolder->getBprive(), $subFolder->getBprive());
        FolderFactory::find($subFolder)->remove();
        FolderFactory::find($parentFolder)->remove();
    }
}
