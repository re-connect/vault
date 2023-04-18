<?php

namespace App\Tests\v2\EventSubscriber\Logs;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\Contact;
use App\EventSubscriber\Logs\PersonalDataSubscriber;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ContactFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;

class PersonalDataSubscriberTest extends AbstractLogActivitySubscriberTest implements TestLogActivitySubscriberInterface
{
    use Factories;

    private const LOG_FILE_NAME = 'personal_data.log';
    private ?PersonalDataSubscriber $personalDataSubscriber;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->personalDataSubscriber = $this->getContainer()->get(PersonalDataSubscriber::class);
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function testEventSubscriptions(): void
    {
        $this->assertEventSubscriptions($this->personalDataSubscriber->getSubscribedEvents());
    }

    public function testPostPersist(): void
    {
        $contact = (new Contact(BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL)->object()))
            ->setNom('test')
            ->setPrenom('test')
            ->setPrenom('test');

        $this->em->persist($contact);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Personal data created', ...$this->getLogContent($contact)]);
    }

    public function testPreUpdate(): void
    {
        $contact = ContactFactory::random()->object();
        $contact->setNom('test');
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Personal data updated', ...$this->getLogContent($contact)]);

        $contact->setBprive(!$contact->getBprive());
        $this->em->flush();
        $logIntro = $contact->getBprive() ? 'Personal data switched private' : 'Personal data switched public';

        $this->assertLastLog(self::LOG_FILE_NAME, [$logIntro, ...$this->getLogContent($contact)]);
    }

    public function testPreRemove(): void
    {
        $contact = ContactFactory::random()->object();
        $this->em->remove($contact);
        $logContent = $this->getLogContent($contact);
        $this->em->flush();

        $this->assertLastLog(self::LOG_FILE_NAME, ['Personal data removed', ...$logContent]);
    }

    private function getLogContent(Contact $contact): array
    {
        return [
            'entity_id' => $contact->getId(),
            'user_id' => $contact->getBeneficiaire()?->getUser()?->getId(),
            'by_user_id' => $this->loggedUser?->getId(),
        ];
    }
}
