<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User extends BaseUser implements \JsonSerializable
{
    private const BASE_USERNAME_REGEXP = '/^[a-z\-]+\.[a-z\-]+(\.[0-3][0-9]\/[0-1][0-9]\/[1-2][0-9]{3})?$/';
    public const USER_TYPE_BENEFICIAIRE = 'ROLE_BENEFICIAIRE';
    public const USER_TYPE_MEMBRE = 'ROLE_MEMBRE';
    public const USER_TYPE_GESTIONNAIRE = 'ROLE_GESTIONNAIRE';
    public const USER_TYPE_ASSOCIATION = 'ROLE_ASSOCIATION';
    public const USER_TYPE_ADMINISTRATEUR = 'ROLE_ADMIN';

    public static array $arTypesUser = [
        self::USER_TYPE_BENEFICIAIRE => 'beneficiaire',
        self::USER_TYPE_MEMBRE => 'membre',
        self::USER_TYPE_GESTIONNAIRE => 'gestionnaire',
        self::USER_TYPE_ASSOCIATION => 'association',
        self::USER_TYPE_ADMINISTRATEUR => 'administrateur',
    ];

    /**
     * @var string
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    protected $username = '';

    /**
     * @var string
     */
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    protected $email;

    /**
     * @var \DateTime|null
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    protected $lastLogin;

    /**
     * @var int
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    protected $id;

    /**
     * @var \DateTime
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $createdAt;

    /**
     * @var \DateTime
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $updatedAt;

    /**
     * @var string
     */
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    private $prenom;

    /**
     * @var string
     */
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    private $nom;

    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    private ?\DateTime $birthDate = null;

    /**
     * @var string
     */
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    private $telephone;

    /** @var string */
    private $telephoneFixe;

    /**
     * @var bool
     *
     * @Groups({ "read", "user:read" })
     */
    #[Groups(['read', 'user:read'])]
    private $bActif = false;

    /**
     * @var string
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $typeUser;

    /** @var string */
    private $privateKey = '';
    /**
     * @var string
     */
    #[Groups(['read', 'user:read'])]
    private $lastIp;

    private ?string $lastLang = null;

    /** @var Administrateur */
    private $subjectAdministrateur;

    /** @var Beneficiaire */
    private $subjectBeneficiaire;

    /** @var Membre */
    private $subjectMembre;

    /** @var Association */
    private $subjectAssociation;

    /** @var Gestionnaire */
    private $subjectGestionnaire;

    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private ?Adresse $adresse = null;

    /**
     * @var bool
     */
    private $firstVisit = true;

    /**
     * @var bool
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $bFirstMobileConnexion = false;

    /**
     * @var Collection
     */
    private $refreshTokens;

    /**
     * @var Collection
     */
    private $accessTokens;

    /**
     * @var \DateTime
     */
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $derniereConnexionAt;

    /**
     * @var string
     */
    #[Groups(['read', 'user:read'])]
    private $avatar;

    /**
     * @var bool
     */
    private $test = false;

    /**
     * @var string
     */
    private $autoLoginToken;

    /**
     * @var \DateTime
     */
    private $autoLoginTokenDeliveredAt;
    private $creators;
    private bool $canada = false;
    private ?string $fcnToken = null;
    private ?bool $isCreationProcessPending = false;
    private ?string $oldUsername = null;

    public function __construct()
    {
        parent::__construct();
        $this->refreshTokens = new ArrayCollection();
        $this->creators = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * @param string $prenom
     *
     * @return User
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param string $nom
     *
     * @return User
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTime $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    /**
     * Get telephone.
     *
     * @return ?string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     *
     * @return User
     */
    public function setTelephone($telephone = null)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return string
     */
    public function getTelephoneFixe()
    {
        return $this->telephoneFixe;
    }

    /**
     * @param string $telephoneFixe
     *
     * @return User
     */
    public function setTelephoneFixe($telephoneFixe)
    {
        $this->telephoneFixe = $telephoneFixe;

        return $this;
    }

    /**
     * @return bool
     */
    public function getBActif()
    {
        return $this->bActif;
    }

    /**
     * @param bool $bActif
     *
     * @return User
     */
    public function setBActif($bActif)
    {
        $this->bActif = $bActif;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     *
     * @throws \Exception
     */
    public function setPrivateKey($privateKey)
    {
        throw new \Exception('Private key is set at object construction');
    }

    /**
     * @return string
     */
    public function getLastIp()
    {
        return $this->lastIp;
    }

    /**
     * @param string $lastIp
     *
     * @return User
     */
    public function setLastIp($lastIp)
    {
        $this->lastIp = $lastIp;

        return $this;
    }

    public function getLastLang(): ?string
    {
        return $this->lastLang;
    }

    public function setLastLang(?string $lastLang): self
    {
        $this->lastLang = $lastLang;

        return $this;
    }

    public function __toString()
    {
        return sprintf('%s (id:%s)', $this->username, $this->id);
    }

    /**
     * @return Administrateur|Association|Beneficiaire|Gestionnaire|Membre|null
     */
    public function getSubject()
    {
        if ($this->isBeneficiaire()) {
            return $this->getSubjectBeneficiaire();
        }
        if ($this->isGestionnaire()) {
            return $this->getSubjectGestionnaire();
        }
        if ($this->isMembre()) {
            return $this->getSubjectMembre();
        }
        if ($this->isAssociation()) {
            return $this->getSubjectAssociation();
        }
        if ($this->isAdministrateur()) {
            return $this->getSubjectAdministrateur();
        }

        return null;
    }

    public function isBeneficiaire(): bool
    {
        return self::USER_TYPE_BENEFICIAIRE === $this->typeUser || $this->subjectBeneficiaire;
    }

    public function getSecretAnswer(): string
    {
        return !$this->getSubjectBeneficiaire() ? '' : $this->getSubjectBeneficiaire()->getReponseSecrete();
    }

    public function getSecretQuestion(): string
    {
        return !$this->getSubjectBeneficiaire() ? '' : $this->getSubjectBeneficiaire()->getQuestionSecrete();
    }

    /**
     * @return Beneficiaire
     */
    public function getSubjectBeneficiaire()
    {
        return $this->subjectBeneficiaire;
    }

    public function setSubjectBeneficiaire(Beneficiaire $subjectBeneficiaire = null): self
    {
        $this->subjectBeneficiaire = $subjectBeneficiaire;

        if ($subjectBeneficiaire) {
            $this->typeUser = self::USER_TYPE_BENEFICIAIRE;
        }

        return $this;
    }

    public function isGestionnaire(): bool
    {
        return self::USER_TYPE_GESTIONNAIRE === $this->typeUser;
    }

    public function getSubjectGestionnaire(): ?Gestionnaire
    {
        return $this->subjectGestionnaire;
    }

    public function setSubjectGestionnaire(Gestionnaire $subjectGestionnaire = null): self
    {
        $this->subjectGestionnaire = $subjectGestionnaire;

        return $this;
    }

    public function isAssociation(): bool
    {
        return self::USER_TYPE_ASSOCIATION == $this->typeUser;
    }

    public function getSubjectAssociation(): ?Association
    {
        return $this->subjectAssociation;
    }

    public function setSubjectAssociation(Association $subjectAssociation = null): self
    {
        $this->subjectAssociation = $subjectAssociation;

        return $this;
    }

    public function hasMemberAccess(): bool
    {
        return $this->isMembre() || $this->isGestionnaire() || $this->isAdministrateur();
    }

    public function isValidUser(): bool
    {
        return $this->isBeneficiaire() || $this->hasMemberAccess();
    }

    public function isMembre(): bool
    {
        return self::USER_TYPE_MEMBRE === $this->typeUser || $this->subjectMembre;
    }

    public function getSubjectMembre(): ?Membre
    {
        return $this->subjectMembre;
    }

    public function setSubjectMembre(Membre $subjectMembre = null): self
    {
        $this->subjectMembre = $subjectMembre;

        if ($subjectMembre) {
            $this->typeUser = self::USER_TYPE_MEMBRE;
        }

        return $this;
    }

    public function isAdministrateur(): bool
    {
        return self::USER_TYPE_ADMINISTRATEUR === $this->typeUser;
    }

    public function getSubjectAdministrateur(): ?Administrateur
    {
        return $this->subjectAdministrateur;
    }

    public function setSubjectAdministrateur(Administrateur $subjectAdministrateur = null): self
    {
        $this->subjectAdministrateur = $subjectAdministrateur;

        return $this;
    }

    public function getFirstVisit(): bool
    {
        return $this->firstVisit;
    }

    public function setFirstVisit(?bool $firstVisit = false): self
    {
        $this->firstVisit = $firstVisit;

        return $this;
    }

    public function getBFirstMobileConnexion(): ?bool
    {
        return $this->bFirstMobileConnexion;
    }

    public function setBFirstMobileConnexion(?bool $bFirstMobileConnexion = false): self
    {
        $this->bFirstMobileConnexion = $bFirstMobileConnexion;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function addRefreshToken(RefreshToken $refreshToken): self
    {
        $this->refreshTokens[] = $refreshToken;

        return $this;
    }

    public function removeRefreshToken(RefreshToken $refreshToken): self
    {
        $this->refreshTokens->removeElement($refreshToken);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getRefreshTokens()
    {
        return $this->refreshTokens;
    }

    public function addAccessToken(AccessToken $accessToken): self
    {
        $this->accessTokens[] = $accessToken;

        return $this;
    }

    public function removeAccessToken(AccessToken $accessToken): self
    {
        $this->accessTokens->removeElement($accessToken);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAccessTokens()
    {
        return $this->accessTokens;
    }

    public function toSonataString(): string
    {
        return sprintf(
            '%s (%s)',
            $this->getUserIdentifier(),
            $this->getTypeUser()
        );
    }

    public function getTypeUser(): ?string
    {
        return $this->typeUser;
    }

    public function setTypeUser(?string $typeUser): self
    {
        $this->typeUser = $typeUser;
        $this->setRoles([$typeUser]);

        return $this;
    }

    public function createdAtToString()
    {
        return $this->getCreatedAt()->format('d/m/Y H:i');
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTestToString(): string
    {
        return $this->test ? 'Oui' : '';
    }

    public function derniereConnexionAtToString(): ?string
    {
        if (null !== ($derniereConnexionAt = $this->getDerniereConnexionAt())) {
            return $derniereConnexionAt->format('d/m/Y H:i');
        }

        return null;
    }

    public function getPreviousLoginString(): ?string
    {
        return $this->derniereConnexionAt ? $this->derniereConnexionAt->format('Y-m-d') : null;
    }

    public function hasLoginToday(): bool
    {
        return $this->getPreviousLoginString() === (new \DateTime())->format('Y-m-d');
    }

    public function getDerniereConnexionAt(): ?\DateTime
    {
        return $this->derniereConnexionAt;
    }

    public function setDerniereConnexionAt(?\DateTime $derniereConnexionAt): self
    {
        $this->derniereConnexionAt = $derniereConnexionAt;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatar2ImagePath(): string
    {
        return 'uploads/user/avatar_'.uniqid();
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    public function setTest(?bool $test = false): self
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize($withSubject = false): array
    {
        $data = [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'username' => $this->username,
            'type_user' => $this->typeUser,
//            'username_canonical' => $this->usernameCanonical,
            'email' => $this->email,
//            'email_canonical' => $this->emailCanonical,
//            'enabled' => $this->enabled,
            'last_login' => null !== $this->derniereConnexionAt ? $this->derniereConnexionAt->format(\DateTime::W3C) : null,
//            'groups' => $this->groups,
//            'roles' => $this->roles,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
//            'b_actif' => $this->bActif,
            'b_first_mobile_connexion' => $this->bFirstMobileConnexion,
            'adresse' => $this->getAdresse(),
        ];

        if ($withSubject) {
            switch ($this->typeUser) {
                case self::USER_TYPE_BENEFICIAIRE:
                    $data['beneficiaire'] = $this->subjectBeneficiaire->jsonSerialize(false);
                    break;
                case self::USER_TYPE_MEMBRE:
                    $data['membre'] = $this->subjectMembre->jsonSerialize(false);
                    break;
                case self::USER_TYPE_GESTIONNAIRE:
                    $data['gestionnaire'] = $this->subjectGestionnaire->jsonSerialize(false);
                    break;
                default:
                    break;
            }
        }

        return $data;
    }

    public function getAdresse(): ?Adresse
    {
        return $this->adresse;
    }

    public function setAdresse(Adresse $adresse = null): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @throws \Exception
     *
     * @since 5.4.0
     */
    public function jsonSerializeAPI()
    {
        $data = [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'telephone_fixe' => $this->telephoneFixe,
            'username' => $this->username,
            'type_user' => $this->typeUser,
            'email' => $this->email,
            'last_login' => null !== $this->derniereConnexionAt ? $this->derniereConnexionAt->format(\DateTime::W3C) : null,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'b_first_mobile_connexion' => $this->bFirstMobileConnexion,
            'adresse' => $this->getAdresse(),
        ];

        switch ($this->typeUser) {
            case self::USER_TYPE_BENEFICIAIRE:
                //                $data = array_merge($data , [
                //                   'id' => $this->subjectBeneficiaire->getId(),
                //                   'date_naissance' => $this->subjectBeneficiaire->getDateNaissance()->format(DateTime::W3C),
                //                   'total_file_size' => $this->subjectBeneficiaire->getTotalFileSize(),
                //                   'centres' => $this->subjectBeneficiaire->getCentreNoms()->toArray(),
                //                   'question_secrete' => $this->subjectBeneficiaire->getQuestionSecrete(),
                //               ]);

                $data = array_merge($data, $this->subjectBeneficiaire->jsonSerializeAPI());
                //                $data['beneficiaire'] = $this->subjectBeneficiaire->jsonSerialize(false);
                break;
            case self::USER_TYPE_MEMBRE:
                $data = array_merge($data, $this->subjectMembre->jsonSerializeAPI());
                //                $data['membre'] = $this->subjectMembre->jsonSerialize(false);
                break;
            case self::USER_TYPE_GESTIONNAIRE:
                $data = array_merge($data, $this->subjectGestionnaire->jsonSerializeAPI());
                //                $data['gestionnaire'] = $this->subjectGestionnaire->jsonSerialize(false);
                break;
            default:
                break;
        }

        return $data;
    }

    public function getCentresToString(?string $item = 'id'): string
    {
        $get = 'get'.ucfirst($item);
        $str = '';
        $centres = $this->getCentres();
        if (!$centres->isEmpty()) {
            $first = true;
            foreach ($centres as $centre) {
                $str .= !$first ? ',' : '';
                $str .= $centre->$get();
                $first = false;
            }
        }

        return $str;
    }

    public function getCentres()
    {
        switch ($this->getTypeUser()) {
            case self::USER_TYPE_BENEFICIAIRE:
                $subject = $this->getSubjectBeneficiaire();
                break;
            case self::USER_TYPE_MEMBRE:
                $subject = $this->getSubjectMembre();
                break;
            case self::USER_TYPE_GESTIONNAIRE:
                $subject = $this->getSubjectGestionnaire();
                break;
            default:
                $subject = null;
                break;
        }

        return !$subject ? new ArrayCollection([]) : $subject->getCentres();
    }

    /**
     * Remove creator.
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeCreator(Creator $creator)
    {
        return $this->creators->removeElement($creator);
    }

    /**
     * @return Collection|Creator[]
     */
    public function getCreators()
    {
        return $this->creators;
    }

    public function getCreatorClient(): ?CreatorClient
    {
        $creator = $this->creators->filter(static function ($creator) {
            return $creator instanceof CreatorClient;
        })->first();

        return false === $creator ? null : $creator;
    }

    public function isCanada(): bool
    {
        return $this->canada;
    }

    public function setCanada(bool $canada): self
    {
        $this->canada = $canada;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->accessTokens = [];
            $this->adresse = null === $this->adresse ? null : clone $this->adresse;
            $this->canada = true;
            if (null !== $this->subjectBeneficiaire || null !== $this->subjectMembre) {
                $creators = new ArrayCollection();
                if ($creatorUser = $this->getCreatorUser()) {
                    $creators->add(clone $creatorUser);
                }
                if ($creatorCentre = $this->getCreatorCentre()) {
                    $creators->add(clone $creatorCentre);
                }
                $this->creators = new ArrayCollection();
                foreach ($creators as $creator) {
                    $this->addCreator($creator);
                }
            } else {
                $this->creators = new ArrayCollection();
            }

            $this->refreshTokens = [];
            $this->subjectAdministrateur = null;
            $this->subjectAssociation = null;
            $this->subjectBeneficiaire = null;
            $this->subjectMembre = null;
            $this->subjectGestionnaire = null;
        }
    }

    public function getCreatorUser(): ?CreatorUser
    {
        $creator = $this->creators->filter(static function ($creator) {
            return $creator instanceof CreatorUser;
        })->first();

        return false === $creator ? null : $creator;
    }

    public function getCreatorCentre(): ?CreatorCentre
    {
        $creator = $this->creators->filter(static function ($creator) {
            return $creator instanceof CreatorCentre;
        })->first();

        return false === $creator ? null : $creator;
    }

    public function addCreator(Creator $creator): self
    {
        $this->creators[] = $creator;
        $creator->setUser($this);

        return $this;
    }

    public function addCreatorRelay(Centre $relay): self
    {
        return $this->addCreator(new CreatorCentre($relay));
    }

    public function addCreatorClient(Client $client): self
    {
        return $this->addCreator(new CreatorClient($client));
    }

    public function getAutoLoginToken(): ?string
    {
        return $this->autoLoginToken;
    }

    public function createAutoLoginToken(): UuidInterface
    {
        $this->autoLoginToken = Uuid::uuid4();

        return $this->autoLoginToken;
    }

    public function setAutoLoginToken(string $autoLoginToken = null): self
    {
        $this->autoLoginToken = $autoLoginToken;

        return $this;
    }

    public function getAutoLoginTokenDeliveredAt(): \DateTime
    {
        return $this->autoLoginTokenDeliveredAt;
    }

    public function setAutoLoginTokenDeliveredAt(\DateTime $autoLoginTokenDeliveredAt): self
    {
        $this->autoLoginTokenDeliveredAt = $autoLoginTokenDeliveredAt;

        return $this;
    }

    public function getFcnToken(): ?string
    {
        return $this->fcnToken;
    }

    public function setFcnToken(string $fcnToken = null): self
    {
        $this->fcnToken = $fcnToken;

        return $this;
    }

    public static function validatePassword(?string $password, ExecutionContextInterface $context, $payload): void
    {
        if (null === $password) {
            return;
        }
        $criteria = [
            'special' => preg_match('/(?=.*\W)/', $password),
            'number' => preg_match('/\d/', $password),
            'lowercase' => preg_match('/[a-z]/', $password),
            'uppercase' => preg_match('/[A-Z]/', $password),
        ];

        $violations = [];

        foreach ($criteria as $key => $criterion) {
            if (!$criterion) {
                $violations[$key] = $context->buildViolation('password_criterion_'.$key)
                    ->setTranslationDomain('messages')
                    ->atPath('plainPassword');
            }
        }

        $nbViolations = count($violations);
        if ($nbViolations > 1) {
            $context->buildViolation('password_help_criteria', ['{{ atLeast }}' => $nbViolations - 1, '{{ total }}' => $nbViolations])
                ->setTranslationDomain('messages')
                ->atPath('plainPassword')
                ->addViolation();

            foreach ($violations as $violation) {
                $violation->addViolation();
            }
        }
    }

    public function refreshLastPasswordUpdateDate(PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('password')) {
            $this->setPasswordUpdatedAt(new \DateTimeImmutable());
        }
    }

    public function getDefaultUsername(): string
    {
        return $this->getSubject()?->getDefaultUserName() ?? sprintf('%s.%s', $this->getSluggedLastname(), $this->getSluggedFirstName());
    }

    /** @return Collection<int, UserCentre> */
    public function getUserCentres(): Collection
    {
        $subject = $this->getSubject();

        if ($subject instanceof UserWithCentresInterface) {
            return $subject->getUsersCentres();
        }

        return new ArrayCollection();
    }

    /** @return ReadableCollection<int, UserCentre> */
    public function getValidUserCentres(): ReadableCollection
    {
        return $this->getUserCentres()->filter(fn (UserCentre $userCentre) => $userCentre->getBValid());
    }

    /** @return ReadableCollection<int, Centre> */
    public function getRelays(): ReadableCollection
    {
        return $this->getUserRelays()
            ->map(fn (UserCentre $userCentre) => $userCentre->getCentre());
    }

    /** @return ReadableCollection<int, Centre> */
    public function getValidRelays(): ReadableCollection
    {
        return $this->getValidUserCentres()
            ->map(fn (UserCentre $userCentre) => $userCentre->getCentre());
    }

    /** @return Collection<int, UserCentre> */
    public function getUserRelays(): Collection
    {
        return $this->isBeneficiaire()
            ? $this->getSubjectBeneficiaire()->getBeneficiairesCentres()
            : $this->getSubjectMembre()->getMembresCentres();
    }

    public function getUserRelay(Centre $relay): ?UserCentre
    {
        $userRelays = $this->getUserRelays()->filter(fn (UserCentre $userRelay) => $userRelay->getCentre() === $relay);

        return $userRelays->first() ?? null;
    }

    public static function createUserRelay(User $user, Centre $relay): UserCentre
    {
        $userRelay = $user->isBeneficiaire() ? new BeneficiaireCentre() : new MembreCentre();

        return $userRelay->setCentre($relay)->setUser($user);
    }

    public function isCreationProcessPending(): ?bool
    {
        return $this->isCreationProcessPending;
    }

    public function setIsCreationProcessPending(?bool $isCreationProcessPending): self
    {
        $this->isCreationProcessPending = $isCreationProcessPending;

        return $this;
    }

    public function hasCreator(): bool
    {
        return $this->creators->count() > 0;
    }

    public function getSluggedFirstName(): string
    {
        return (new AsciiSlugger())
            ->slug($this->prenom ?? '')->replaceMatches("#[ \'-]#", '')->lower()->toString();
    }

    public function getSluggedLastname(): string
    {
        return (new AsciiSlugger())
            ->slug($this->nom ?? '')->replaceMatches("#[ \'-]#", '')->lower()->toString();
    }

    public function hasSuffixedUsername(): bool
    {
        return !preg_match(self::BASE_USERNAME_REGEXP, $this->username);
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getAffiliatedRelaysWithBeneficiaryManagement(): Collection
    {
        if ($this->isMembre()) {
            return $this->getSubjectMembre()->getAffiliatedRelaysWithBeneficiaryManagement();
        }

        return new ArrayCollection();
    }

    /**
     * @return Collection <int, Centre>
     */
    public function getAffiliatedRelaysWithProfessionalManagement(): Collection
    {
        if ($this->isMembre()) {
            return $this->getSubjectMembre()->getAffiliatedRelaysWithProfessionalManagement();
        }

        return new ArrayCollection();
    }

    /** @return Collection <int, UserCentre> */
    public function getSubjectRelays(): Collection
    {
        return $this->isBeneficiaire()
            ? $this->getSubjectBeneficiaire()->getBeneficiairesCentres()
            : $this->getSubjectMembre()->getMembresCentres();
    }

    public function isLinkedToRelay(Centre $relay): bool
    {
        return null !== $this->getSubjectRelaysForRelay($relay);
    }

    public function getSubjectRelaysForRelay(Centre $relay): UserCentre|null
    {
        return $this->getSubjectRelays()
            ->filter(fn (UserCentre $subjectRelay) => $subjectRelay->getCentre() === $relay)
            ->first() ?: null;
    }

    public function getOldUsername(): ?string
    {
        return $this->oldUsername;
    }

    public function setOldUsername(?string $oldUsername): self
    {
        $this->oldUsername = $oldUsername;

        return $this;
    }

    public function hasDroit(string $droit): bool
    {
        return $this->getValidUserCentres()->exists(fn (int $index, UserCentre $userCentre) => $userCentre->hasDroit($droit));
    }
}
