<?php

namespace App\Tests\v2\EventSubscriber\Logs;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;
use Doctrine\ORM\Events;

class AbstractLogActivitySubscriberTest extends AuthenticatedKernelTestCase
{
    private ?string $logDir;
    protected ?User $loggedUser;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->logDir = sprintf('%s/activity', $this->getContainer()->get('kernel')->getLogDir());

        $this->loggedUser = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object()->getUser();
        $this->loginUser(MemberFixture::MEMBER_MAIL);
    }

    public function assertEventSubscriptions(array $eventSubscriptions): void
    {
        $subscribedEvents = [Events::postPersist, Events::preUpdate, Events::preRemove];
        array_map(fn (string $eventName) => $this->assertContains($eventName, $eventSubscriptions), $subscribedEvents);
    }

    public function assertLastLog(string $fileName, array $logContents): void
    {
        $file = file(sprintf('%s/%s', $this->logDir, $fileName));
        $lastLog = end($file);

        array_map(fn (string $content) => self::assertStringContainsString($content, $lastLog), $logContents);
    }
}
