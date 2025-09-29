<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\BeneficiaryDto;
use App\Api\Dto\LinkBeneficiaryDto;
use App\Api\Manager\ApiClientManager;
use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\Client;
use App\Entity\Attributes\ClientBeneficiaire;
use App\ManagerV2\UserManager;
use App\Repository\BeneficiaireRepository;
use App\Repository\MembreRepository;
use App\ServiceV2\Helper\RelayAssignationHelper;
use App\Validator\Constraints\PasswordCriteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
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
        private ValidatorInterface $validator
    ) {
    }

    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $this->entityManager->clear();
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
            $this->updateExternalLink($data, $client);
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

    private function updateExternalLink(Beneficiaire $data, Client $client): void
    {
        $externalLink = $data->getExternalLinkForClient($client);
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
