<?php

namespace App\ServiceV2\Helper;

use App\Entity\BeneficiaireCentre;
use App\Entity\Client;
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

    public function assignRelayFromExternalId(User $user, Client $client): void
    {
        if (!$user->getExternalRelayId()) {
            return;
        }
        try {
            $relay = $this->repository->findByDistantId($user->getExternalRelayId(), $client->getRandomId());
            /** @var BeneficiaireCentre $userRelay */
            $userRelay = User::createUserRelay($user, $relay);
            $user->getSubjectBeneficiaire()->addBeneficiairesCentre($userRelay);
            $this->em->persist($userRelay);

            $beneficiary = $user->getSubjectBeneficiaire();
            $distantId = $beneficiary->distantId;
            if ($distantId) {
                $beneficiary->addClientExternalLink($client, $distantId, $user->getExternalProId(), $userRelay);
            }
        } catch (\Exception $e) {
            $this->apiLogger->error(sprintf('Did not find any center on vault for distant id %s when creating user %s from client %s', $user->getExternalRelayId(), $user->getId(), $client->getRandomId()));
        }
    }
}
