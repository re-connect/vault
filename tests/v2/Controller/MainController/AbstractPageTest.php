<?php

namespace App\Tests\v2\Controller\MainController;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractPageTest extends WebTestCase
{
    private KernelBrowser $client;
    private TranslatorInterface $translator;
    protected const URL = '';
    protected const PAGE_TITLE = '';
    protected const SELECTOR = 'h2';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->translator = $this->client->getContainer()->get('translator');
    }

    public function testPage(): void
    {
        $this->client->request('GET', static::URL);
        $this->assertResponseIsSuccessful();
        $title = $this->translator->trans(static::PAGE_TITLE);
        $this->assertSelectorTextContains(static::SELECTOR, $title);
    }
}
