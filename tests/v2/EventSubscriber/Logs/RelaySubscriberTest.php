<?php

namespace App\Tests\v2\EventSubscriber\Logs;

use App\Entity\Centre;
use App\EventSubscriber\Logs\RelaySubscriber;
use App\Tests\Factory\AssociationFactory;
use App\Tests\Factory\RelayFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class RelaySubscriberTest extends AbstractLogActivitySubscriberTest implements TestLogActivitySubscriberInterface
{
    use Factories;

    private const LOG_FILE_NAME = 'relay.log';
    private ?RelaySubscriber $relaySubscriber;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->relaySubscriber = $this->getContainer()->get(RelaySubscriber::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function testEventSubscriptions(): void
    {
        $this->assertEventSubscriptions($this->relaySubscriber->getSubscribedEvents());
    }

    public function testPostPersist(): void
    {
        $relay = (new Centre())
            ->setNom('test')
            ->setAssociation(AssociationFactory::random()->object());

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
