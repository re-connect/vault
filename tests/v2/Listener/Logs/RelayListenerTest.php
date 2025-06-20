<?php

namespace App\Tests\v2\Listener\Logs;

use App\Entity\Centre;
use App\ListenerV2\Logs\RelayListener;
use App\Tests\Factory\RelayFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class RelayListenerTest extends AbstractLogActivityListenerTest implements TestLogActivityListenerInterface
{
    use Factories;

    private const LOG_FILE_NAME = 'relay.log';
    private ?RelayListener $relaySubscriber;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->relaySubscriber = $this->getContainer()->get(RelayListener::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function testPostPersist(): void
    {
        $relay = (new Centre())
            ->setNom('test');

        $this->em->persist($relay);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Relay created', ...$this->getLogContent($relay)]);
    }

    public function testPreUpdate(): void
    {
        $relay = RelayFactory::random()->object();
        $relay->setNom('test');
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Relay updated', ...$this->getLogContent($relay)]);
    }

    public function testPreRemove(): void
    {
        $relay = RelayFactory::random()->object();
        $this->em->remove($relay);
        $logContent = $this->getLogContent($relay);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Relay removed', ...$logContent]);
    }

    private function getLogContent(Centre $relay): array
    {
        return [
            'user_id' => $relay->getId(),
            'by_user_id' => $this->loggedUser?->getId(),
        ];
    }
}
