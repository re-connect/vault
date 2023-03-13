<?php

namespace App\Tests\v2\Controller;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractControllerTest extends AuthenticatedTestCase
{
    protected static TranslatorInterface $translator;
    private static DataCollectorTranslator $dataCollectorTranslator;

    public static function setUpBeforeClass(): void
    {
        self::ensureKernelShutdown();
        $container = self::createClient()->getContainer();
        self::$translator = $container->get(TranslatorInterface::class);
        self::$dataCollectorTranslator = $container->get(TranslatorInterface::class);
    }

    public function assertRoute(
        string $url,
        int $expectedStatusCode,
        ?string $userMail = null,
        ?string $expectedRedirect = null,
        string $method = 'GET',
        bool $isXmlHttpRequest = false,
        array $body = [],
    ): KernelBrowser {
        self::ensureKernelShutdown();
        $clientTest = static::createClient();

        if ($userMail) {
            $user = $this->getTestUserFromDb($userMail);
            $clientTest->loginUser($user);
        }

        $isXmlHttpRequest
            ? $clientTest->xmlHttpRequest($method, $url, $body)
            : $clientTest->request($method, $url, $body);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        if ($expectedRedirect) {
            $this->assertResponseRedirects($expectedRedirect);
        }

        return $clientTest;
    }

    /**
     * @param array<string,string> $values
     */
    public function assertFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        if ($email) {
            $user = $this->getTestUserFromDb($email);
            $client->loginUser($user);
        }
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton(self::$translator->trans($formSubmit))->form();
        $form->setValues($values);
        $client->submit($form);

        if ($redirectUrl) {
            $this->assertResponseStatusCodeSame(302);
            $this->assertResponseRedirects($redirectUrl);
        } else {
            $this->assertResponseStatusCodeSame(200);
        }
    }

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     */
    public function assertFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        if ($email) {
            $user = $this->getTestUserFromDb($email);
            $client->loginUser($user);
        }
        $crawler = $client->request('GET', $url);
        $form = $crawler->selectButton(self::$translator->trans($formSubmit))->form();
        $form->setValues($values);
        $client->submit($form);

        foreach ($errors as $error) {
            $this->assertSelectorTextContains($alternateSelector ?? 'span.help-block', $this->getValidationErrorMessage($error));
        }
        $this->assertResponseStatusCodeSame(422);
        $this->assertRouteSame($route);
    }

    /**
     * @param array<string, ?string> $error
     */
    private function getValidationErrorMessage(array $error): string
    {
        return self::$translator->trans(
            $error['message'],
            $error['params'] ?? [],
            self::$dataCollectorTranslator->getCatalogue()->defines($error['message'])
                ? 'messages'
                : 'validators'
        );
    }

    public function getTestUserFromDb(string $email): User
    {
        return UserFactory::find(['email' => $email])->object();
    }

    /**
     * @param int[]|string[] $params
     */
    public function buildUrlString(string $format, array $params): string
    {
        return sprintf($format, ...$params);
    }
}
