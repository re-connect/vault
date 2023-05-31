<?php

namespace App\Tests\v2\Smoke\Public;

use App\Tests\v2\Smoke\AbstractSmokeTest;

class ApplicationAvailabilityFunctionalTest extends AbstractSmokeTest
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful(string $url): void
    {
        $this->assertRoute($url);
    }

    public function urlProvider(): \Generator
    {
        yield ['/'];
        yield ['/login'];
        yield ['/public/newsletter-confirmation'];
        yield ['/reconnect-accompagnement-numerique'];
        yield ['/reconnect-la-solution-pro'];
        yield ['/nous-contacter'];
        yield ['/reconnect-le-coffre-fort-numerique'];
        yield ['/faq-rgpd'];
        yield ['/login'];
        yield ['/reset-password/choose'];
        yield ['/reset-password/email'];
        yield ['/reset-password/sms'];
        yield ['/reset-password/check-sms'];
        yield ['/reset-password/reset/sms/{token}'];
        yield ['/reset-password/reset/email/{token}'];
        yield ['/logout'];
    }
}
