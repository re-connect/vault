<?php

namespace App\Tests\v2\Controller\Admin;

use App\DataFixtures\v2\AdminFixture;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\Controller\AbstractControllerTest;
use Zenstruck\Foundry\Test\Factories;

class BeneficiaireAdminTest extends AbstractControllerTest
{
    use Factories;

    private const string CREATE_URL = '/admin/app/beneficiaire/create';
    private const string APPEND_FORM_ELEMENT_URL = '/admin/core/append-form-field-element';
    private const string NEW_BENEFICIARY_LASTNAME = 'Beneficiary LastName';
    private const string BIRTHDATE = '1990-01-01';

    /**
     * @dataProvider provideTestCanAddRelayRow
     */
    public function testCanAddRelayRow(bool $isCreation): void
    {
        $uniqid = uniqid('s');
        $beneficiaryId = $isCreation ? null : BeneficiaireFactory::createOne()->getId();
        $url = $this->buildUrl(self::APPEND_FORM_ELEMENT_URL, [
            '_sonata_admin' => 'sonata.admin.beneficiaire',
            'elementId' => $uniqid.'_beneficiairesCentres',
            'uniqid' => $uniqid,
            'objectId' => $beneficiaryId,
        ]);

        // Like the browser, the "+ Ajouter" button posts the whole form, with its prefilled birthdate
        $body = [$uniqid => ['dateNaissance' => self::BIRTHDATE]];
        $client = $this->assertRoute($url, 200, AdminFixture::ADMIN_MAIL, null, 'POST', true, $body);

        $this->assertStringContainsString(
            $uniqid.'_beneficiairesCentres_0_centre',
            $client->getResponse()->getContent(),
        );
    }

    public static function provideTestCanAddRelayRow(): \Generator
    {
        yield 'Should add a relay row when creating a beneficiary' => [true];
        yield 'Should add a relay row when editing a beneficiary' => [false];
    }

    public function testCreateBeneficiaryWithRelay(): void
    {
        $relay = RelayFactory::createOne();
        $uniqid = uniqid('s');
        $url = $this->buildUrl(self::CREATE_URL, ['uniqid' => $uniqid]);

        $client = $this->assertRoute($url, 200, AdminFixture::ADMIN_MAIL);
        $csrfToken = $client->getCrawler()
            ->filter('input[name="'.$uniqid.'[_token]"]')
            ->attr('value');

        $client->request('POST', $url, [
            $uniqid => [
                '_token' => $csrfToken,
                'user' => [
                    'nom' => self::NEW_BENEFICIARY_LASTNAME,
                    'prenom' => 'Admin',
                    'plainPassword' => [
                        'first' => UserFactory::STRONG_PASSWORD_CLEAR,
                        'second' => UserFactory::STRONG_PASSWORD_CLEAR,
                    ],
                ],
                'dateNaissance' => self::BIRTHDATE,
                'beneficiairesCentres' => [
                    ['centre' => $relay->getId(), 'bValid' => '1'],
                ],
            ],
        ]);
        $this->assertResponseRedirects();

        $user = UserFactory::find(['nom' => self::NEW_BENEFICIARY_LASTNAME]);
        $beneficiaryRelays = $user->getSubjectBeneficiaire()->getBeneficiairesCentres();

        $this->assertCount(1, $beneficiaryRelays);
        $this->assertSame($relay->getId(), $beneficiaryRelays->first()->getCentre()->getId());
    }

    /**
     * Null parameters are left out of the query string.
     *
     * @param array<string, string|int|null> $query
     */
    private function buildUrl(string $path, array $query): string
    {
        return $path.'?'.http_build_query($query);
    }
}
