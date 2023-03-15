<?php

namespace App\DataFixtures;

use App\Entity\AccessToken;
use App\Entity\Administrateur;
use App\Entity\Association;
use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\ClientCentre;
use App\Entity\ClientEntity;
use App\Entity\Contact;
use App\Entity\Creator;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\Note;
use App\Entity\RefreshToken;
use App\Entity\SharedDocument;
use App\Entity\TypeCentre;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model as OauthModel;

class FixturesPurger implements ORMPurgerInterface
{
    private ?EntityManagerInterface $em;

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

    public function setEntityManager(EntityManagerInterface $em): self
    {
        $this->em = $em;

        return $this;
    }
}
