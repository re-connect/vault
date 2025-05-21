<?php

namespace App\Tests\v2\Listener\Logs;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\Attributes\User;
use App\Tests\Factory\MembreFactory;
use App\Tests\v2\AuthenticatedKernelTestCase;

abstract class AbstractLogActivityListenerTest extends AuthenticatedKernelTestCase
{
    private ?string $logDir;
    protected ?User $loggedUser;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->logDir = sprintf('%s/activity_test', $this->getContainer()->get('kernel')->getLogDir());

        $this->loggedUser = MembreFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object()->getUser();
        $this->loginUser(MemberFixture::MEMBER_MAIL);
    }

    public function assertLastLog(string $fileName, array $logContents): void
    {
        $file = file(sprintf('%s/%s', $this->logDir, $fileName));
        $lastLog = end($file);

        array_map(fn (string $content) => self::assertStringContainsString($content, $lastLog), $logContents);
    }
}
