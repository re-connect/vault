<?php

namespace App\Command;

use App\Entity\ClientMembre;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\User;
use App\Repository\CentreRepository;
use App\Repository\ClientGestionnaireRepository;
use App\Repository\GestionnaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-gestionnaire-to-member',
    description: 'Migrate gestionnaire user to new member user',
)]
class MigrateGestionnaireToMemberCommand extends Command
{
    public function __construct(
        private readonly GestionnaireRepository $gestionnaireRepository,
        private readonly EntityManagerInterface $em,
        private readonly CentreRepository $relayRepository,
        private readonly ClientGestionnaireRepository $clientGestionnaireRepository,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $gestionnaires = $this->gestionnaireRepository->findAll();
        $newMembersCount = 0;
        $removedGestionnaires = 0;

        $io->info(sprintf('Found %d gestionnaires', count($gestionnaires)));

        $progressBar = new ProgressBar($output, count($gestionnaires));
        $progressBar->start();

        foreach ($gestionnaires as $gestionnaire) {
            if ($this->isFakeGestionnaire($gestionnaire)) {
                $this->removeGestionnaire($gestionnaire);
                ++$removedGestionnaires;
            } else {
                $this->migrateToMember($gestionnaire);
                ++$newMembersCount;
            }
            $progressBar->advance();
        }

        $this->em->flush();

        $progressBar->finish();

        $io->info(sprintf('%d gestionnaires removed', $removedGestionnaires));
        $io->success(sprintf('%d gestionnaires migrated to members', $newMembersCount));

        return Command::SUCCESS;
    }

    private function removeGestionnaire(Gestionnaire $gestionnaire): void
    {
        $relays = $this->relayRepository->findBy(['gestionnaire' => $gestionnaire]);

        foreach ($relays as $relay) {
            $relay->setAssociation($gestionnaire->getAssociation());
            $relay->setGestionnaire();
        }

        $this->em->remove($gestionnaire);
        $this->em->remove($gestionnaire->getUser());
    }

    private function migrateToMember(Gestionnaire $gestionnaire): void
    {
        $user = $gestionnaire->getUser();
        $member = (new Membre())
            ->setCreatedAt($gestionnaire->getCreatedAt())
            ->setWasGestionnaire(true)
            ->setUser($user);

        $user->setRoles([User::USER_TYPE_MEMBRE]);
        $user->setSubjectGestionnaire();
        $user->setSubjectMembre($member);

        $relays = $this->relayRepository->findBy(['gestionnaire' => $gestionnaire]);
        $externalLinks = $this->clientGestionnaireRepository->findBy(['entity' => $gestionnaire]);

        foreach ($relays as $relay) {
            $member->addMembresCentre(
                (new MembreCentre())
                    ->setDroits([MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true, MembreCentre::TYPEDROIT_GESTION_MEMBRES => true])
                    ->setMembre($member)
                    ->setCentre($relay)
                    ->setBValid(true)
            );

            $relay->setAssociation($gestionnaire->getAssociation());
            $relay->setGestionnaire();
        }

        foreach ($externalLinks as $externalLink) {
            $distant_id = $externalLink->getDistantId();
            $this->em->remove($externalLink);
            $member->addExternalLink((new ClientMembre())
                ->setEntity($member)
                ->setDistantId($distant_id)
                ->setClient($externalLink->getClient()));
        }

        $this->em->remove($gestionnaire);
        $this->em->persist($member);
    }

    private function isFakeGestionnaire(Gestionnaire $gestionnaire): bool
    {
        $user = $gestionnaire->getUser();

        return str_contains($user->getUsername(), 'gestionnaire') || str_contains($user->getEmail(), '@reconnect.fr');
    }
}
