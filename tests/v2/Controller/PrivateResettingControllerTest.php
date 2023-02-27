<?php

namespace App\Tests\v2\Controller;

use App\Entity\User;
use App\RepositoryV2\ResetPasswordRequestRepository;
use App\Tests\v2\AuthenticatedTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PrivateResettingControllerTest extends AuthenticatedTestCase
{
    private KernelBrowser $client;
    private ResetPasswordRequestRepository $resetPasswordRequestRepository;
    private User $proUser;
    private User $beneficiaryUser;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->container = self::getContainer();
        $this->resetPasswordRequestRepository = $this->container->get(ResetPasswordRequestRepository::class);
        $this->passwordHasher = $this->container->get(UserPasswordHasherInterface::class);

        $this->em = $this->getEntityManager();
        $this->beneficiaryUser = $this->createTestBeneficiary('reset_tests@mail.com')->getUser();
        $this->proUser = $this->createTestUser('admin@mail.com', ['ROLE_ADMIN'], 'ROLE_ADMIN');
        $this->proUser->setTypeUser('ROLE_ADMIN');
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $request = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->beneficiaryUser);
        if ($request) {
            $this->em->remove($request);
            $this->em->flush();
        }
        $this->removeTestUser('admin@mail.com');
        $this->removeTestUser('reset_tests@mail.com');
    }

    public function testPrivateResetEmail(): void
    {
        $this->client->loginUser($this->proUser);
        $this->client->request('GET', sprintf('/user/%d/reset-password/email', $this->beneficiaryUser->getId()));

        // Check if password request is correct
        $firstPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->beneficiaryUser);
        self::assertSame($firstPasswordRequest->getUser()->getUsername(), $this->beneficiaryUser->getUsername());
        self::assertNull($firstPasswordRequest->getSmsCode());
        self::assertNull($firstPasswordRequest->getSmsToken());
        // We add 24h + 10 seconds because this assertions can be executed a few seconds later than the request
        self::assertLessThan((new \DateTime())->modify('+24 hours')->modify('+10 seconds'), $firstPasswordRequest->getExpiresAt());

        // Second request, password request entity must be the same
        $this->client->request('GET', sprintf('/user/%d/reset-password/email', $this->beneficiaryUser->getId()));
        $secondPasswordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->beneficiaryUser);

        self::assertEquals($firstPasswordRequest->getUser()->getId(), $secondPasswordRequest->getUser()->getId());
        self::assertSame($firstPasswordRequest->getId(), $secondPasswordRequest->getId());
    }

    public function testPrivateRequestSms(): void
    {
        $this->beneficiaryUser->setTelephone('0611111111');
        $this->em->flush();

        $this->client->loginUser($this->proUser);
        $crawler = $this->client->request('GET', sprintf('/user/%d/reset-password/sms', $this->beneficiaryUser->getId()));
        $passwordRequest = $this->resetPasswordRequestRepository->getMostRecentNonExpiredRequest($this->beneficiaryUser);
        self::assertSame($passwordRequest->getUser(), $this->beneficiaryUser);
        self::assertNotNull($passwordRequest->getSmsCode());
        self::assertNotNull($passwordRequest->getSmsToken());

        // Fill form with wrong smsCode
        $form = $crawler->selectButton('Confirmer')->form();
        $formName = $form->getName();
        $form->setValues([
            'password_reset_sms[smsCode]' => sprintf('%sFAKECODE', $passwordRequest->getSmsCode()),
            'password_reset_sms[password][first]' => 'newPassword',
            'password_reset_sms[password][second]' => 'newPassword',
        ]);
        $this->client->submit($form);

        // So we stay on the same page
        $form = $crawler->selectButton('Confirmer')->form();
        self::assertEquals($formName, $form->getName());

        // Fill with correct smsCode
        $form->setValues([
            'password_reset_sms[smsCode]' => $passwordRequest->getSmsCode(),
            'password_reset_sms[password][first]' => 'newPassword',
            'password_reset_sms[password][second]' => 'newPassword',
        ]);
        $this->client->submit($form);

        // Get recent instance of EntityManager and test password
        $em = $this->container->get(EntityManagerInterface::class);
        $this->beneficiaryUser = $em->getRepository(User::class)->find($this->beneficiaryUser->getId());
        self::assertTrue($this->passwordHasher->isPasswordValid($this->beneficiaryUser, 'newPassword'));
    }

    public function testPrivateRequestQuestion(): void
    {
        $this->beneficiaryUser->getSubjectBeneficiaire()
            ->setQuestionSecrete('Où fait-il le plus beau ?')
            ->setReponseSecrete('Rennes');
        $this->em->flush();

        $this->client->loginUser($this->proUser);
        $crawler = $this->client->request('GET', sprintf('/user/%d/reset-password/question', $this->beneficiaryUser->getId()));

        // Fill form with wrong secret answer
        $form = $crawler->selectButton('Confirmer')->form();
        $form->setValues([
            'password_reset_secret_question[answer]' => 'Paris',
            'password_reset_secret_question[password][first]' => 'newPassword',
            'password_reset_secret_question[password][second]' => 'newPassword',
        ]);
        $this->client->submit($form);

        // So we stay on the same page
        $form = $crawler->selectButton('Confirmer')->form();
        self::assertResponseStatusCodeSame(422);

        // Fill with correct secret answer
        $form->setValues([
            'password_reset_secret_question[answer]' => 'Rennes',
            'password_reset_secret_question[password][first]' => 'newPassword',
            'password_reset_secret_question[password][second]' => 'newPassword',
        ]);
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(302);

        // Get recent instance of EntityManager and test password
        $em = $this->container->get(EntityManagerInterface::class);
        $this->beneficiaryUser = $em->getRepository(User::class)->find($this->beneficiaryUser->getId());
        self::assertTrue($this->passwordHasher->isPasswordValid($this->beneficiaryUser, 'newPassword'));
    }

    public function testPrivateRequestRandom(): void
    {
        // Pro reset password with random code and logout
        $this->client->loginUser($this->proUser);
        $crawler = $this->client->request('GET', sprintf('/user/%d/reset-password/random', $this->beneficiaryUser->getId()));
        $form = $crawler->selectButton('Réinitialisation')->form();
        $crawler = $this->client->submit($form);
        $text = $crawler->filter('div.text-center.col-12 > span')->text();
        $newPassword = substr($text, -8);
        $this->client->request('GET', '/logout');

        // Beneficiary try to login with new password
        $crawler = $this->client->request('GET', '/');
        $form = $crawler->selectButton('Connexion')->form();
        $form->setValues([
            '_username' => $this->beneficiaryUser->getEmail(),
            '_password' => $newPassword,
        ]);
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(302);

        // Successful login with new password, beneficiary can access documents
        $this->client->request('GET', sprintf('/beneficiary/%d/documents', $this->beneficiaryUser->getSubjectBeneficiaire()->getId()));
        self::assertResponseStatusCodeSame(200);
    }
}
