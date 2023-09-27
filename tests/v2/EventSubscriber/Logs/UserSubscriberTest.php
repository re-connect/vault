<?php

namespace App\Tests\v2\EventSubscriber\Logs;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\EventSubscriber\Logs\UserSubscriber;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class UserSubscriberTest extends AbstractLogActivitySubscriberTest implements TestLogActivitySubscriberInterface
{
    use Factories;

    private const LOG_FILE_NAME = 'user.log';
    private ?UserSubscriber $userSubscriber;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSubscriber = $this->getContainer()->get(UserSubscriber::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function testEventSubscriptions(): void
    {
        $this->assertEventSubscriptions($this->userSubscriber->getSubscribedEvents());
    }

    public function testPostPersist(): void
    {
        $user = (new User())
            ->setUsername('test')
            ->setPassword('test')
            ->setPrenom('test')
            ->setNom('test')
            ->setTypeUser('test')
            ->setEmail('test')
            ->setTelephone('test');

        $this->em->persist($user);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['User created', ...$this->getLogContent($user)]);
    }

    public function testPreUpdate(): void
    {
        $user = UserFactory::random()->object();
        $user->setNom('test');
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['User updated', ...$this->getLogContent($user)]);
    }

    public function testPreRemove(): void
    {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL)->object();
        $this->em->remove($user->getSubject());
        $this->em->remove($user);
        $logContent = $this->getLogContent($user);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['User removed', ...$logContent]);
    }

    private function getLogContent(User $user): array
    {
        return [
            'user_id' => $user->getId(),
            'by_user_id' => $this->loggedUser?->getId(),
        ];
    }
}
