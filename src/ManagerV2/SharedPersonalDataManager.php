<?php

namespace App\ManagerV2;

use App\Entity\Attributes\SharedDocument;
use App\Entity\Attributes\SharedFolder;
use App\Entity\Attributes\SharedPersonalData;
use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Factory\SharedPersonalDataFactory;
use App\Repository\UserRepository;
use App\ServiceV2\Mailer\MailerService;
use App\ServiceV2\Traits\SessionsAwareTrait;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class SharedPersonalDataManager
{
    use UserAwareTrait;
    use SessionsAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SharedPersonalDataFactory $factory,
        private readonly MailerService $mailerService,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function generateSharedPersonalDataAndSendEmail(DonneePersonnelle $personalData, string $email, string $locale = 'fr'): void
    {
        $user = $this->getUser();
        if (!$user || !$personalData->getBeneficiaire()) {
            $this->addFlashMessage('danger', 'user_not_found');

            return;
        }
        $sharedPersonalData = $this->factory->generateSharedPersonalData($user, $personalData, $email, $locale);
        $this->mailerService->sendSharedDocumentLink($sharedPersonalData, $email);

        $this->addFlashMessage('success', 'share_document_success');
    }

    public function validateTokenAndFetchPersonalData(string $token): ?SharedPersonalData
    {
        $decodedToken = $this->decodeSharedPersonalDataToken($token);
        [
            'selector' => $selector,
            'user_id' => $userId,
            'personal_data_id' => $personalDataId,
            'personal_data_class' => $personalDataClass,
        ] = $decodedToken;

        /** @var ?SharedPersonalData $sharedPersonalData * */
        $sharedPersonalData = $this->getSharedPersonalDataRepository($personalDataClass)?->findOneBy(['selector' => $selector]);

        $errorMessage = $this->getErrorMessage($sharedPersonalData, (int) $userId, (string) $personalDataClass, (int) $personalDataId);

        if ($errorMessage) {
            $this->addFlashMessage('danger', $errorMessage);
        }

        return $sharedPersonalData;
    }

    private function getErrorMessage(?SharedPersonalData $sharedPersonalData, int $userId, string $personalDataClass, int $personalDataId): ?string
    {
        return match (true) {
            !$sharedPersonalData => 'share_document_error_no_shared_document',
            $sharedPersonalData->isExpired() => 'share_document_error_expired',
            !$this->userRepository->find($userId) || !$this->getPersonalDataRepository($personalDataClass)?->find($personalDataId) => 'share_document_error_not_found',
            default => null,
        };
    }

    /**
     * @return string[]
     */
    private function decodeSharedPersonalDataToken(string $token): array
    {
        return json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))), true);
    }

    /**
     * @return EntityRepository<SharedDocument>|EntityRepository<SharedFolder>|null
     */
    private function getSharedPersonalDataRepository(string $personalDataClass): ?EntityRepository
    {
        return match ($personalDataClass) {
            Dossier::class => $this->em->getRepository(SharedFolder::class),
            Document::class => $this->em->getRepository(SharedDocument::class),
            default => null,
        };
    }

    /**
     * @return EntityRepository<Document>|EntityRepository<Dossier>|null
     */
    private function getPersonalDataRepository(string $personalDataClass): ?EntityRepository
    {
        return match ($personalDataClass) {
            Dossier::class => $this->em->getRepository(Dossier::class),
            Document::class => $this->em->getRepository(Document::class),
            default => null,
        };
    }
}
