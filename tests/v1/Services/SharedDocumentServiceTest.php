<?php

namespace App\Tests\v1\Services;

use ApiPlatform\Api\UrlGeneratorInterface;
use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\User;
use App\Factory\SharedDocumentFactory;
use App\ManagerV2\SharedDocumentManager;
use App\Repository\DocumentRepository;
use App\Repository\SharedDocumentRepository;
use App\Repository\UserRepository;
use App\ServiceV2\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class SharedDocumentServiceTest extends KernelTestCase
{
    private SharedDocumentManager $manager;
    private User $user;
    private Beneficiaire $beneficiaire;
    private Document $document;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    public function testGenerateSharedDocumentAndSendEmail(): void
    {
        $this->manager->generateSharedDocumentAndSendEmail($this->document, 'gandalf@gmail.com', $this->user);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateTokenAndFetchDocument(): void
    {
        $this->manager->validateTokenAndFetchDocument($this->generateToken());
        $this->expectNotToPerformAssertions();
    }

    private function generateToken(): string
    {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]);
        $payload = json_encode([
            'user_id' => $this->user->getId(),
            'document_id' => $this->document->getId(),
            'expiration_date' => new \DateTime('+7 days'),
            'selector' => $this->generateSelector(),
        ]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader.'.'.$base64UrlPayload, $this->generateSelector(), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $base64UrlHeader.'.'.$base64UrlPayload.'.'.$base64UrlSignature;

        return $jwt;
    }

    private function generateSelector(): string
    {
        $string = '';
        for ($i = 0; $i < 24; ++$i) {
            $string .= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'[random_int(0, strlen('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') - 1)];
        }

        return str_shuffle($string);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();
        $repository = $this->createMock(SharedDocumentRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $documentRepository = $this->createMock(DocumentRepository::class);
        $this->em = $container->get('doctrine')->getManager();
        $this->userRepository = $this->em->getRepository(User::class);
        $tokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $factory = new SharedDocumentFactory($this->em, $tokenManager, $urlGenerator);
        $mailerService = $this->createMock(MailerService::class);
        $requestStack = $this->createMock(RequestStack::class);
        $security = $container->get(Security::class);

        $this->manager = new SharedDocumentManager(
            $repository,
            $factory,
            $mailerService,
            $userRepository,
            $documentRepository,
            $requestStack,
            $security
        );

        if (null === $this->userRepository->findOneBy(['username' => 'gandalf.grey.28/06/1990'])) {
            $this->user = $this->generateUser();
            $this->em->persist($this->user);
            $this->beneficiaire = $this->generateBeneficiaire($this->user);
            $this->em->persist($this->beneficiaire);
            $this->document = $this->generateDocument($this->beneficiaire);
        } else {
            $this->user = $this->userRepository->findOneBy(['username' => 'gandalf.grey.28/06/1990']);
            $this->beneficiaire = $this->user->getSubjectBeneficiaire();
            $this->em->persist($this->beneficiaire);
            $this->document = $this->beneficiaire->getDocuments()[0];
        }
        $this->em->persist($this->document);
        $this->em->flush();
    }

    private function generateUser(): User
    {
        return (new User())->setNom('Gandalf')
            ->setPrenom('Grey')
            ->setUsername('gandalf.grey.28/06/1990')
            ->setPassword('lalilulelo')
            ->setTypeUser('ROLE_MEMBRE');
    }

    private function generateBeneficiaire($user): Beneficiaire
    {
        return (new Beneficiaire())
            ->setDateNaissance(new \DateTime('now'))
            ->setUser($user);
    }

    private function generateDocument($beneficiaire): Document
    {
        return (new Document())
            ->setNom('mon document')
            ->setTaille(500)
            ->setBeneficiaire($beneficiaire);
    }
}
