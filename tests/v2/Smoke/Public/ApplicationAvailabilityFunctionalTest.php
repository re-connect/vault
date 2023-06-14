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
        yield ['/public/reset-password/choose'];
        yield ['/public/reset-password/email'];
        yield ['/public/reset-password/sms'];
        yield ['/public/reset-password/check-sms'];
        yield ['/public/reset-password/reset/sms/{token}'];
        yield ['/public/reset-password/reset/email/{token}'];
        yield ['/logout'];
    }
}
