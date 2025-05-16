<?php

namespace App\DataFixtures;

use App\Entity\Attributes\AccessToken;
use App\Entity\Attributes\Administrateur;
use App\Entity\Attributes\Association;
use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\BeneficiaireCentre;
use App\Entity\Attributes\Centre;
use App\Entity\Attributes\Client;
use App\Entity\Attributes\ClientCentre;
use App\Entity\Attributes\ClientEntity;
use App\Entity\Attributes\Contact;
use App\Entity\Attributes\Creator;
use App\Entity\Attributes\Document;
use App\Entity\Attributes\Dossier;
use App\Entity\Attributes\Evenement;
use App\Entity\Attributes\Gestionnaire;
use App\Entity\Attributes\Membre;
use App\Entity\Attributes\MembreCentre;
use App\Entity\Attributes\Note;
use App\Entity\RefreshToken;
use App\Entity\SharedDocument;
use App\Entity\TypeCentre;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model as OauthModel;

class FixturesPurger implements ORMPurgerInterface
{
    private ?EntityManagerInterface $em = null;

    #[\Override]
    public function purge(): void
    {
        $this->truncateTables([
            OauthModel\AccessToken::class,
            OauthModel\RefreshToken::class,
            OauthModel\AuthorizationCode::class,
            OauthModel\Client::class,
            ClientEntity::class,
            Creator::class,
            Note::class,
            Evenement::class,
            Contact::class,
            RefreshToken::class,
            SharedDocument::class,
            Document::class,
            Dossier::class,
            MembreCentre::class,
            BeneficiaireCentre::class,
            Centre::class,
            TypeCentre::class,
            Administrateur::class,
            Gestionnaire::class,
            Association::class,
            Creator::class,
            AccessToken::class,
            Client::class,
            ClientCentre::class,
            Beneficiaire::class,
            Membre::class,
            User::class,
        ]);
    }

    public function truncateTable(string $entityName): void
    {
        $this->em->createQueryBuilder()
            ->delete($entityName)
            ->getQuery()
            ->execute();
    }

    public function truncateTables(array $entityNames): void
    {
        foreach ($entityNames as $entityName) {
            $this->truncateTable($entityName);
        }
    }

    #[\Override]
    public function setEntityManager(EntityManagerInterface $em): self
    {
        $this->em = $em;

        return $this;
    }
}
