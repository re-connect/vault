<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Dto\LinkBeneficiaryDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\ClientBeneficiaire;
use App\Entity\ClientCentre;
use App\ManagerV2\UserManager;
use App\Repository\BeneficiaireRepository;
use App\Repository\CentreRepository;
use App\Repository\MembreRepository;
use App\Security\HelperV2\Oauth2Helper;
use App\Security\VoterV2\RelayVoter;
use App\ServiceV2\Helper\RelayAssignationHelper;
use App\Validator\Constraints\PasswordCriteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BeneficiaryStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ApiClientManager $apiClientManager,
        private ProcessorInterface $persistProcessor,
        private RelayAssignationHelper $relayAssignationHelper,
        private MembreRepository $membreRepository,
        private LoggerInterface $apiLogger,
        private UserManager $userManager,
        private BeneficiaireRepository $beneficiaryRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private ValidatorInterface $validator,
        private Oauth2Helper $oauth2Helper,
        private CentreRepository $centreRepository,
        private Security $security,
    ) {
    }

    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof Patch) {
            // Fixes a strange personal data update when linking a beneficiary (see 1ce4c22d)
            $this->entityManager->clear();
        }
        $client = $this->apiClientManager->getCurrentOldClient();

        if ($data instanceof LinkBeneficiaryDto && $operation instanceof Patch && $client) {
            $beneficiary = $this->beneficiaryRepository->find($uriVariables['id']);
            if (!$beneficiary->canAddExternalLinkForClient($client, $data->distantId)) {
                throw new UnprocessableEntityHttpException('This beneficiary already has a link for this client.');
            }

            $this->createExternalLink($data, $client, $beneficiary);

            return $beneficiary;
        }

        if ($data instanceof Beneficiaire && $operation instanceof Patch && $client) {
            $beneficiary = $this->beneficiaryRepository->find($uriVariables['id']);
            $this->updateExternalLink($beneficiary, $data, $client);
            $this->entityManager->flush();

            return $beneficiary;
        }

        if ($data instanceof BeneficiaryDto && $operation instanceof Post) {
            $data = $data->toBeneficiary();
            if (!$data->getUser()?->getPlainPassword()) {
                $data->getUser()?->setPlainPassword($this->userManager->getRandomPassword());
            }
            $errors = $this->validator->validate($data->getUser()->getPlainPassword(), new PasswordCriteria());
            if (0 < $errors->count()) {
                throw new UnprocessableEntityHttpException($this->translator->trans('password_help', ['%minLength%' => 9], 'messages', 'en'));
            }

            $this->createBeneficiary($data, $client);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * Like the v2 API, a pro creates the beneficiary in relays they manage, and the relay links are accepted.
     */
    private function createBeneficiaryForPro(Beneficiaire $data): void
    {
        $relayIds = $data->getUser()->getRelaysIds();
        if (!$relayIds) {
            throw new UnprocessableEntityHttpException('At least one center is required');
        }

        $relays = [];
        foreach ($relayIds as $relayId) {
            $relay = $this->centreRepository->find($relayId);
            if (!$relay || !$this->security->isGranted(RelayVoter::MANAGE_BENEFICIARIES, $relay)) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid center id %s', $relayId));
            }
            $relays[] = $relay;
        }

        $this->relayAssignationHelper->assignRelaysFromIdsArray($data->getUser(), true);
        $data->getUser()->addCreatorRelay($relays[0]);
    }

    private function updateExternalLink(Beneficiaire $beneficiary, Beneficiaire $data, Client $client): void
    {
        $externalLink = $beneficiary->getExternalLinkForClient($client);
        $externalLink?->setDistantId($data->getDistantId());
    }

    private function createExternalLink(LinkBeneficiaryDto $data, Client $client, Beneficiaire $beneficiary): void
    {
        $externalRelayId = $data->externalCenter;
        if ($externalRelayId) {
            if ($beneficiary->hasExternalLinkForRelay($client, $externalRelayId)) {
                throw new UnprocessableEntityHttpException('This beneficiary already has a link for this client and center.');
            }
            $beneficiary->setDistantId($data->distantId);
            $beneficiary->getUser()?->setExternalProId($data->externalProId);
            $this->relayAssignationHelper->assignRelayFromExternalId($beneficiary->getUser(), $client, $externalRelayId);
            $this->entityManager->flush();

            return;
        }

        $externalLink = ClientBeneficiaire::createForMember($client, $data->distantId, $data->externalProId);
        $beneficiary->addExternalLink($externalLink);
        $this->entityManager->persist($externalLink);
        $this->entityManager->flush();
    }

    private function createBeneficiary(Beneficiaire $data, ?Client $client): void
    {
        if (!$this->oauth2Helper->isAuthenticatedAsClient()) {
            $this->createBeneficiaryForPro($data);

            return;
        }

        foreach ($data->getUser()->getRelaysIds() as $relayId) {
            $relay = $this->centreRepository->find($relayId);
            if (!$relay || !$relay->getExternalLinks()->exists(fn (int $key, ClientCentre $link) => $link->getClient() === $client)) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid center id %s', $relayId));
            }
        }
        $this->relayAssignationHelper->assignRelaysFromIdsArray($data->getUser());
        if (!$client) {
            return;
        }
        $this->relayAssignationHelper->assignRelayFromExternalId($data->getUser(), $client, null, true);
        try {
            $pro = $this->membreRepository->findByDistantId($data->getUser()->getExternalProId(), $client->getRandomId());
            if ($pro) {
                $data->getUser()->addCreatorUser($pro->getUser());
            }
        } catch (NonUniqueResultException) {
            $this->apiLogger->error(sprintf('Multiple pros found for distant id %s and client %s', $data->getUser()->getExternalProId(), $client->getRandomId()));
        }
    }
}
