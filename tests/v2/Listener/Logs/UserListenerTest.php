<?php

namespace App\Tests\v2\Listener\Logs;

use App\DataFixtures\v2\MemberFixture;
use App\Entity\User;
use App\ListenerV2\Logs\UserListener;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class UserListenerTest extends AbstractLogActivityListenerTest implements TestLogActivityListenerInterface
{
    use Factories;

    private const LOG_FILE_NAME = 'user.log';
    private ?UserListener $userSubscriber;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSubscriber = $this->getContainer()->get(UserListener::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
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
