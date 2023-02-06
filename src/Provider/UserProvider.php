<?php

namespace App\Provider;

use App\Entity\User;
use App\Security\Authorization\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserProvider
{
    private EntityManagerInterface $em;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        EntityManagerInterface $em,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Création d'un username pour le user (avec incrémentation si il existe déjà pour les membres)
     * Cas traités: bénficiaire (avec date de naissance) et les autres.
     */
    public function formatUserName(User $user, \DateTime $dateDeNaissanceObject = null): void
    {
        $unwanted_array = ['Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y',
            'ą' => 'a', 'č' => 'c', 'ć' => 'c', 'ę' => 'e', 'ė' => 'e', 'į' => 'i', 'ł' => 'l', 'ń' => 'n', 'ü' => 'u', 'ų' => 'u',
            'ż' => 'z', 'ź' => 'z', 'Ą' => 'A', 'Č' => 'C', 'Ć' => 'C', 'Ę' => 'E', 'Ė' => 'E', 'Ł' => 'L', 'Į' => 'I', 'Ń' => 'N',
            'Ų' => 'U', 'Ÿ' => 'Y',
        ];

        $prenom = strtr($user->getPrenom(), $unwanted_array);
        $nom = strtr($user->getNom(), $unwanted_array);

        $prenom = strtolower(preg_replace("#[ \']#", '', $prenom));
        $nom = strtolower(preg_replace("#[ \']#", '', $nom));

        $repository = $this->em->getRepository(User::class);

        if ($user->isBeneficiaire()) {
            if (null === $dateDeNaissanceObject) {
                return;
            }
            $dateNaissance = $dateDeNaissanceObject->format('d/m/Y');
            $baseUsername = $prenom.'.'.$nom.'.'.$dateNaissance;
        } else {
            $baseUsername = $nom.'.'.$prenom;
        }
        $i = 1;
        $usernameNew = $baseUsername;
        while (null !== $repository->findByUsername($usernameNew)) {
            $usernameNew = $baseUsername.'-'.$i++;
        }

        $user->setUsername($usernameNew)->setUsernameCanonical($usernameNew);
    }

    public function getEntity($id): User
    {
        if (!$entity = $this->em->find(User::class, $id)) {
            throw new NotFoundHttpException('No user found for id '.$id);
        }

        if (false === $this->authorizationChecker->isGranted(UserVoter::GESTION_USER, $entity)) {
            throw new AccessDeniedException('');
        }

        return $entity;
    }
}
