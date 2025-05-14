<?php

namespace App\ServiceV2\Helper;

use App\Entity\Attributes\BeneficiaireCentre;
use App\Entity\Attributes\Client;
use App\Entity\Attributes\UserCentre;
use App\Entity\User;
use App\Repository\CentreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class RelayAssignationHelper
{
    public function __construct(private CentreRepository $repository, private EntityManagerInterface $em, private LoggerInterface $apiLogger)
    {
    }

    public function assignRelaysFromIdsArray(User $user): void
    {
        foreach ($user->getRelaysIds() as $relayId) {
            $relay = $this->repository->find($relayId);
            $userRelay = User::createUserRelay($user, $relay);
            $this->em->persist($userRelay);
        }
    }

    public function assignRelayFromExternalId(User $user, Client $client, ?int $externalRelayId = null, bool $acceptRelay = false): void
    {
        $externalRelayId ??= $user->getExternalRelayId();
        if (!$externalRelayId) {
            return;
        }
        try {
            $relay = $this->repository->findByDistantId($externalRelayId, $client->getRandomId());
            /** @var BeneficiaireCentre $userRelay */
            $userRelay = $user->getUserRelays()->filter(fn (UserCentre $userCentre) => $relay === $userCentre->getCentre())->first() ?: User::createUserRelay($user, $relay, $acceptRelay);
            $user->getSubjectBeneficiaire()->addBeneficiairesCentre($userRelay);
            $this->em->persist($userRelay);

            $beneficiary = $user->getSubjectBeneficiaire();
            $distantId = $beneficiary->distantId;
            if ($distantId) {
                $beneficiary->addClientExternalLink($client, $distantId, $user->getExternalProId(), $userRelay);
            }
        } catch (\Exception) {
            $this->apiLogger->error(sprintf('Did not find any center on vault for distant id %s when creating user %s from client %s', $externalRelayId, $user->getId(), $client->getRandomId()));
        }
    }
}
