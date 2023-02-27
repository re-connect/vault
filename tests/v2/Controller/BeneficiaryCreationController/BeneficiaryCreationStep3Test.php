<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Beneficiaire;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use App\Tests\v2\Controller\TestFormInterface;
use App\Tests\v2\Controller\TestRouteInterface;

class BeneficiaryCreationStep3Test extends AbstractControllerTest implements TestRouteInterface, TestFormInterface
{
    private const URL = '/beneficiary/create/3/%s';
    private const FORM_VALUES = [
        'create_beneficiary[questionSecreteChoice]' => '',
        'create_beneficiary[reponseSecrete]' => 'secretAnswer',
    ];

    /** @dataProvider provideTestRoute */
    public function testRoute(string $url, int $expectedStatusCode, ?string $userMail = null, ?string $expectedRedirect = null, string $method = 'GET'): void
    {
        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertRoute($url, $expectedStatusCode, $userMail, $expectedRedirect, $method);
    }

    public function provideTestRoute(): ?\Generator
    {
        yield 'Should redirect to login when not authenticated' => [self::URL, 302, null, '/login'];
        yield 'Should return 200 status code when authenticated as professional' => [self::URL, 200, MemberFixture::MEMBER_MAIL];
        yield 'Should return 403 status code when authenticated as beneficiary' => [self::URL, 403, BeneficiaryFixture::BENEFICIARY_MAIL];
    }

    /**
     * @param array<string, string> $values
     *
     * @dataProvider provideTestFormIsValid
     */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        $firstTranslatedQuestion = array_key_first($this->getTranslatedSecretQuestions());
        $values['create_beneficiary[questionSecreteChoice]'] = $firstTranslatedQuestion;

        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $redirectUrl = sprintf($redirectUrl, $creationProcess->getId());

        $this->assertFormIsValid($url, $formSubmit, $values, $email, $redirectUrl);
    }

    public function provideTestFormIsValid(): ?\Generator
    {
        yield 'Should redirect to step 4 when form is correct' => [
            self::URL,
            'confirm',
            self::FORM_VALUES,
            MemberFixture::MEMBER_MAIL,
            '/beneficiary/create/4/%s',
        ];
    }

    /**
//     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        $firstTranslatedQuestion = array_key_first($this->getTranslatedSecretQuestions());
        $values['create_beneficiary[questionSecreteChoice]'] = $firstTranslatedQuestion;

        $creationProcess = BeneficiaryCreationProcessFactory::findOrCreate(['isCreating' => true, 'remotely' => false])->object();
        $url = sprintf($url, $creationProcess->getId());
        $this->assertFormIsNotValid($url, $route, $formSubmit, $values, $errors, $email, $alternateSelector);
    }

    public function provideTestFormIsNotValid(): ?\Generator
    {
        $values = self::FORM_VALUES;
        $values['create_beneficiary[reponseSecrete]'] = '';

        yield 'Should return an error when answer is empty' => [
            self::URL,
            'create_beneficiary',
            'confirm',
            $values,
            [
                [
                    'message' => 'secret_answer_not_empty',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];

        $values = self::FORM_VALUES;
        $values['create_beneficiary[reponseSecrete]'] = '12';

        yield 'Should return an error when answer length < 3 ' => [
            self::URL,
            'create_beneficiary',
            'confirm',
            $values,
            [
                [
                    'message' => 'Cette chaîne est trop courte. Elle doit avoir au minimum 3 caractères.',
                    'params' => null,
                ],
            ],
            MemberFixture::MEMBER_MAIL,
            'div.invalid-feedback',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getTranslatedSecretQuestions(): array
    {
        $secretQuestions = [];
        foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
            $secretQuestions[self::$translator->trans($key)] = self::$translator->trans($value);
        }

        return $secretQuestions;
    }
}
