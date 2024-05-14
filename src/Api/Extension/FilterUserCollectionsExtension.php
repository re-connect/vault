<?php

namespace App\Api\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\BeneficiaireRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class FilterUserCollectionsExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    #[\Override]
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();

        match ($resourceClass) {
            Centre::class => $this->filterRelays($queryBuilder, $rootAlias, $user),
            User::class => $this->filterUsers($queryBuilder, $rootAlias, $user),
            Beneficiaire::class => $this->filterBeneficiaries($queryBuilder, $rootAlias, $user),
            Document::class, Dossier::class, Contact::class, Note::class, Evenement::class => $this->filterPersonalData($queryBuilder, $rootAlias, $user),
            default => null,
        };
    }

    public function filterRelays(QueryBuilder $queryBuilder, string $rootAlias, ?UserInterface $user): void
    {
        $queryBuilder
            ->leftJoin(sprintf('%s.membresCentres', $rootAlias), 'membresCentres')
            ->leftJoin(sprintf('%s.beneficiairesCentres', $rootAlias), 'beneficiairesCentres')
            ->leftJoin('beneficiairesCentres.beneficiaire', 'beneficiaire')
            ->leftJoin('membresCentres.membre', 'membre')
            ->andWhere('membre.user = :current_user OR beneficiaire.user = :current_user')
            ->setParameter('current_user', $user);
    }

    private function filterPersonalData(QueryBuilder $queryBuilder, string $rootAlias, ?UserInterface $user): void
    {
        $beneficiary = $user->getSubjectBeneficiaire();
        if (!$beneficiary) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.beneficiaire = :beneficiaryId', $rootAlias))
            ->setParameter('beneficiaryId', $beneficiary->getId());
    }

    private function filterBeneficiaries(QueryBuilder $queryBuilder, string $rootAlias, ?UserInterface $user): void
    {
        if (!$user instanceof User || !$user->isMembre() || !$user->getSubjectMembre()?->getId()) {
            return;
        }
        $proId = $user->getSubjectMembre()?->getId();

        BeneficiaireRepository::addProAccessJoinsAndConditions($queryBuilder, $rootAlias, $proId);
    }

    private function filterUsers(QueryBuilder $queryBuilder, string $rootAlias, ?UserInterface $user): void
    {
        $queryBuilder
            ->andWhere(sprintf('%s.id = :userId', $rootAlias))
            ->setParameter('userId', $user->getId());
    }
}
