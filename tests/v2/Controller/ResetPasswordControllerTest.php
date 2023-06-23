<?php

namespace App\Tests\v2\Controller;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\Entity\User;
use App\RepositoryV2\ResetPasswordRequestRepository;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\AuthenticatedTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordControllerTest extends AuthenticatedTestCase
{
    private const RESET_EMAIL_URL = '/public/reset-password/email';
    private const RESET_SMS_URL = '/public/reset-password/sms';
    private KernelBrowser $client;
    private ResetPasswordRequestRepository $resetPasswordRequestRepository;
    private User $user;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->container = self::getContainer();
        $this->em = $this->container->get(EntityManagerInterface::class);
        $this->user = UserFactory::find(['email' => BeneficiaryFixture::BENEFICIARY_MAIL])->object();
        $this->resetPasswordRequestRepository = $this->container->get(ResetPasswordRequestRepository::class);
        $this->passwordHasher = $this->container->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        $request = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->user);
        if ($request) {
            $this->em->remove($request);
            $this->em->flush();
        }
    }

    public function testPublicRequestEmail(): void
    {
        // A user request reset password by email
        $crawler = $this->client->request('GET', self::RESET_EMAIL_URL);
        self::assertResponseStatusCodeSame(200);
        $form = $crawler->selectButton('Confirmer')->form();
        $form->setValues([
                'reset_password_request_form[email]' => $this->user->getEmail(),
            ]);
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(200);

        // Check if password request is correct
        $firstPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->user);
        self::assertSame($firstPasswordRequest->getUser()->getUsername(), $this->user->getUsername());
        self::assertNull($firstPasswordRequest->getSmsCode());
        self::assertNull($firstPasswordRequest->getSmsToken());
        // We add 24h + 10 seconds because this assertions can be executed a few seconds later than the request
        self::assertLessThan((new \DateTime())->modify('+24 hours')->modify('+10 seconds'), $firstPasswordRequest->getExpiresAt());

        // Same user try to request password by email directly
        $form = $crawler->selectButton('Confirmer')->form();
        $form->setValues([
            'reset_password_request_form[email]' => $this->user->getEmail(),
        ]);
        $this->client->submit($form);

        // Password request isn't expired for this user, it still the same request
        $secondPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->user);
        self::assertSame($firstPasswordRequest, $secondPasswordRequest);

        // Same user try to request password by sms directly
        $crawler = $this->client->request('GET', self::RESET_SMS_URL);
        $form = $crawler->selectButton('Confirmer')->form();
        $form->setValues([
            'reset_password_request_form[phone]' => $this->user->getTelephone(),
        ]);
        $this->client->submit($form);

        $smsPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->user);
        self::assertSame($firstPasswordRequest, $smsPasswordRequest);

        $this->em->remove($firstPasswordRequest);
        $this->em->flush();
    }

    public function testPublicRequestSms(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', self::RESET_SMS_URL);
        $form = $crawler->selectButton('Confirmer')->form();
        $form->setValues([
            'reset_password_request_form[phone]' => $this->user->getTelephone(),
        ]);
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(200);
        $firstPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->user);
        self::assertSame($firstPasswordRequest->getUser()->getUsername(), $this->user->getUsername());
        self::assertNotNull($firstPasswordRequest->getSmsCode());
        self::assertNotNull($firstPasswordRequest->getSmsToken());

        // Same user try to request password by sms directly
        $form = $crawler->selectButton('Confirmer')->form();
        $form->setValues([
            'reset_password_request_form[phone]' => $this->user->getTelephone(),
        ]);
        $crawler = $this->client->submit($form);

        // Password request isn't expired for this user, it still the same request
        $secondPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->user);
        self::assertSame($firstPasswordRequest, $secondPasswordRequest);

        // Check sms form : fill input with wrong sms code
        $form = $crawler->selectButton('Confirmer')->form();
        self::assertEquals($form->get('reset_password_sms_check_form[phone]')->getValue(), $this->user->getTelephone());
        $form->setValues([
            'reset_password_sms_check_form[smsCode]' => sprintf('%sFAKECODE', $firstPasswordRequest->getSmsCode()),
        ]);
        $crawler = $this->client->submit($form);

        // We stay on the same page
        $form = $crawler->selectButton('Confirmer')->form();
        self::assertEquals('reset_password_sms_check_form', $form->getName());

        // Then we fill with correct SMS code
        $form->setValues([
            'reset_password_sms_check_form[smsCode]' => $firstPasswordRequest->getSmsCode(),
        ]);
        $crawler = $this->client->submit($form);

        // We get the change password form
        $form = $crawler->selectButton('Confirmer')->form();
        self::assertEquals($form->getName(), 'change_password_form');
        $form->setValues([
             'change_password_form[plainPassword][first]' => 'newPassword',
             'change_password_form[plainPassword][second]' => 'newPassword',
         ]);
        $this->client->submit($form);

        // Get recent instance of EntityManager and test password
        $em = $this->container->get(EntityManagerInterface::class);
        $this->user = $em->getRepository(User::class)->find($this->user->getId());
        self::assertTrue($this->passwordHasher->isPasswordValid($this->user, 'newPassword'));
    }
}
