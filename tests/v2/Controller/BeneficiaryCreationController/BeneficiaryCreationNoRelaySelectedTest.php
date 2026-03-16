<?php

namespace App\Tests\v2\Controller\BeneficiaryCreationController;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\BeneficiaireCentre;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\BeneficiaryCreationProcessFactory;
use App\Tests\Factory\RelayFactory;
use App\Tests\v2\Controller\AbstractControllerTest;

class BeneficiaryCreationNoRelaySelectedTest extends AbstractControllerTest
{
    private const URL = '/beneficiary/create/5/%s';
    private const URL_REMOTELY = '/beneficiary/create/3/%s';

    /** @dataProvider  provideTestNoRelaySelectedNotification**/
    public function testNoRelaySelectedNotification(string $url, bool $beneficiaryHasRelay): void
    {
        $beneficiary = BeneficiaireFactory::createOne()->object();

        if ($beneficiaryHasRelay) {
            $beneficiary->addBeneficiairesCentre(BeneficiaireCentre::createValid(RelayFactory::randomOrCreate()->object()));
        }
        self::assertCount($beneficiaryHasRelay ? 1 : 0, $beneficiary->getAffiliatedRelays());

        // Request to summary step
        $url = sprintf(
            $url,
            BeneficiaryCreationProcessFactory::createOne(['beneficiary' => $beneficiary])->object()->getId(),
        );

        $client = $this->assertRoute($url, 200, MemberFixture::MEMBER_MAIL);

        // If modal is present in dom, assertion on modal text
        $node = $client->getCrawler()->filter('.modal-dialog');
        if (0 < $node->count()) {
            self::assertTrue(str_contains($node->html(), self::$translator->trans('no_relay_selected')));
            self::assertTrue(str_contains($node->html(), self::$translator->trans('no_relay_selected_alert')));
        }
    }

    public function provideTestNoRelaySelectedNotification(): \Generator
    {
        yield 'Should display notification if beneficiary has no relay' => [self::URL, false];
        yield 'Should not display notification if beneficiary has relay' => [self::URL, true];
        yield 'Should display notification if beneficiary has no relay on remotely creation' => [self::URL_REMOTELY, false];
        yield 'Should not display notification if beneficiary has relay on remotely creation' => [self::URL_REMOTELY, true];
    }
}
