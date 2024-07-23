<?php

namespace App\Entity\Attributes;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Api\Dto\UserDto;
use App\Api\Filters\UsernameFilter;
use App\Api\State\SearchBeneficiaryProvider;
use App\Api\State\UserPasswordProcessor;
use App\Api\State\UserStateProcessor;
use App\Controller\Api\MeController;
use App\Entity\UserWithCentresInterface;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Erkens\Security\TwoFactorTextBundle\Model\TwoFactorTextInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\Index(columns: ['disabledBy_id'], name: 'IDX_8D93D6493F1E31AA')]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D64992FC23A8', columns: ['username_canonical'])]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D649F85E0677', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D649C05FB297', columns: ['confirmation_token'])]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D6494DE7DC5C', columns: ['adresse_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D649E7927C74', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D649885281E', columns: ['emailCanonical'])]
#[ORM\UniqueConstraint(name: 'UNIQ_8D93D6496F55C0C', columns: ['oldUsername'])]
#[ApiFilter(UsernameFilter::class, properties: ['username' => 'exact'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_OAUTH2_USERS') or is_granted('ROLE_USER')", provider: SearchBeneficiaryProvider::class),
        new Patch(security: "is_granted('UPDATE', object)", processor: UserPasswordProcessor::class),
        new Get(uriTemplate: '/me', controller: MeController::class, security: "is_granted('ROLE_USER')", read: false),
        new Patch(security: "is_granted('UPDATE', object)", input: UserDto::class, processor: UserStateProcessor::class),
    ],
    normalizationContext: ['groups' => ['v3:user:read']],
    denormalizationContext: ['groups' => ['v3:user:write']],
)]
#[Anonymize('reconnect.user_filter')]
class User extends BaseUser implements \JsonSerializable, TwoFactorInterface, TwoFactorTextInterface
{
    private const string BASE_USERNAME_REGEXP = '/^[a-z\-]+\.[a-z\-]+(\.[0-3][0-9]\/[0-1][0-9]\/[1-2][0-9]{3})?$/';
    public const string USER_TYPE_BENEFICIAIRE = 'ROLE_BENEFICIAIRE';
    public const string USER_TYPE_MEMBRE = 'ROLE_MEMBRE';
    public const string USER_TYPE_GESTIONNAIRE = 'ROLE_GESTIONNAIRE';
    public const string USER_TYPE_ASSOCIATION = 'ROLE_ASSOCIATION';
    public const string USER_TYPE_ADMINISTRATEUR = 'ROLE_ADMIN';
    public const string USER_TYPE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const int USER_PASSWORD_LENGTH = 9;

    public const array ADMIN_TYPES = [
        self::USER_TYPE_ADMINISTRATEUR => self::USER_TYPE_ADMINISTRATEUR,
        self::USER_TYPE_SUPER_ADMIN => self::USER_TYPE_SUPER_ADMIN,
    ];
    public const string DEFAULT_LANGUAGE = 'fr';
    public const array LANGUAGES = [
        'ar' => 'ar',
        'de' => 'de',
        'en' => 'en',
        'es' => 'es',
        'fr' => 'fr',
        'gb' => 'en',
        'it' => 'it',
        'ps' => 'ps',
        'prs' => 'prs',
        'ru' => 'ru',
    ];

    public const array USER_TYPES = [
        self::USER_TYPE_BENEFICIAIRE => 'beneficiaire',
        self::USER_TYPE_MEMBRE => 'membre',
        self::USER_TYPE_GESTIONNAIRE => 'gestionnaire',
        self::USER_TYPE_ASSOCIATION => 'association',
        self::USER_TYPE_ADMINISTRATEUR => 'administrateur',
    ];

    public const string MFA_METHOD_SMS = 'sms';
    public const string MFA_METHOD_EMAIL = 'email';
    public const array MFA_METHODS = [
        self::MFA_METHOD_EMAIL,
        self::MFA_METHOD_SMS,
    ];
    public const int MFA_MAX_SEND_CODE_COUNT = 3;

    #[ORM\Column(name: 'username', type: 'string', length: 180, nullable: false)]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    #[Anonymize('md5')]
    protected $username = '';

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    #[Anonymize('email', options: ['domain' => 'yopmail.com'])]
    protected $email;

    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    protected $lastLogin;

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    protected $id;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $createdAt;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $updatedAt;

    #[ORM\Column(name: 'prenom', type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:user:write', 'v3:beneficiary:write'])]
    #[Anonymize('fr-fr.firstname')]
    private $prenom;

    #[ORM\Column(name: 'nom', type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:user:write', 'v3:beneficiary:write'])]
    #[Anonymize('fr-fr.lastname')]
    private $nom;

    #[ORM\Column(name: 'birthDate', type: 'date', nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:user:write', 'v3:beneficiary:write'])]
    #[Anonymize('date', options: ['min' => 'now -70 years', 'max' => 'now -15 years'])]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(name: 'telephone', type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read', 'v3:beneficiary:write'])]
    #[Anonymize('fr-fr.phone')]
    private $telephone;

    #[ORM\Column(name: 'telephoneFixe', type: 'string', length: 255, nullable: true)]
    private $telephoneFixe;

    #[ORM\Column(name: 'bActif', type: 'boolean', nullable: false)]
    #[Groups(['read', 'user:read'])]
    private $bActif = false;

    #[ORM\Column(name: 'typeUser', type: 'string', length: 255, nullable: false)]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $typeUser;

    #[ORM\Column(name: 'privateKey', type: 'string', length: 255, nullable: false)]
    private $privateKey = '';

    #[ORM\Column(name: 'lastIp', type: 'string', length: 20, nullable: false)]
    #[Groups(['read', 'user:read'])]
    private $lastIp;

    #[ORM\Column(name: 'lastLang', type: 'string', length: 3, nullable: true)]
    private ?string $lastLang = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Administrateur::class)]
    private ?Administrateur $subjectAdministrateur = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Beneficiaire::class, cascade: ['persist'])]
    private ?Beneficiaire $subjectBeneficiaire = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Membre::class, cascade: ['persist'])]
    private ?Membre $subjectMembre = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Association::class)]
    private ?Association $subjectAssociation = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Gestionnaire::class)]
    private ?Gestionnaire $subjectGestionnaire = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Adresse::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'adresse_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private ?Adresse $adresse = null;

    #[ORM\Column(name: 'firstVisit', type: 'boolean', nullable: false, options: ['default' => true])]
    private $firstVisit = true;

    #[ORM\Column(name: 'bFirstMobileConnexion', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $bFirstMobileConnexion = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RefreshToken::class, cascade: ['persist', 'remove'])]
    private $refreshTokens;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AccessToken::class, cascade: ['persist', 'remove'])]
    private $accessTokens;

    #[ORM\Column(name: 'derniereConnexionAt', type: 'datetime', nullable: true)]
    #[Groups(['read', 'user:read', 'v3:user:read'])]
    private $derniereConnexionAt;

    #[Groups(['read', 'user:read'])]
    #[ORM\Column(name: 'avatar', type: 'string', length: 255, nullable: true)]
    private $avatar;

    #[ORM\Column(name: 'test', type: 'boolean', nullable: false)]
    private $test = false;

    #[ORM\Column(name: 'autoLoginToken', type: 'string', length: 36, nullable: true)]
    private $autoLoginToken;

    #[ORM\Column(name: 'autoLoginTokenDeliveredAt', type: 'datetime', nullable: true)]
    private $autoLoginTokenDeliveredAt;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Creator::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $creators;

    #[ORM\Column(name: 'canada', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $canada = false;

    #[ORM\Column(name: 'fcnToken', type: 'string', length: 255, nullable: true)]
    private ?string $fcnToken = null;

    #[ORM\Column(name: 'oldUsername', type: 'string', length: 180, nullable: true)]
    #[Anonymize('md5')]
    private ?string $oldUsername = null;
    /** @var ?Collection<int, SharedDocument> */
    #[ORM\OneToMany(mappedBy: 'sharedBy', targetEntity: SharedDocument::class, cascade: ['remove'])]
    private ?Collection $sharedDocuments = null;

    #[ORM\Column(name: 'cgsAcceptedAt', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cgsAcceptedAt = null;

    #[ORM\Column(name: 'personalAccountDataRequestedAt', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $personalAccountDataRequestedAt = null;

    #[ORM\Column(name: 'hasPasswordWithLatestPolicy', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $hasPasswordWithLatestPolicy = false;

    #[ORM\Column(name: 'authCode', type: 'string', length: 255, nullable: true)]
    private ?string $authCode = null;

    #[ORM\Column(name: 'mfaEnabled', type: 'boolean', nullable: true)]
    private ?bool $mfaEnabled = null;

    #[ORM\Column(name: 'mfaPending', type: 'boolean', nullable: true)]
    private ?bool $mfaPending = null;    // This is only used when login from API

    #[ORM\Column(name: 'mfaValid', type: 'boolean', nullable: true)]
    private ?bool $mfaValid = null;    // This is only used when login from API

    #[ORM\Column(name: 'mfaMethod', type: 'string', length: 255, nullable: true, options: ['default' => self::MFA_METHOD_EMAIL])]
    private string $mfaMethod = self::MFA_METHOD_EMAIL;

    #[ORM\Column(name: 'mfaRetryCount', type: 'integer', nullable: true)]
    private ?int $mfaRetryCount = 0;

    #[ORM\Column(name: 'mfaCodeGeneratedAt', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $mfaCodeGeneratedAt = null;

    /** @var string[] */
    private array $relaysIds = [];
    private ?string $externalRelayId = null;
    private ?string $externalProId = null;

    public function __construct()
    {
        parent::__construct();
        $this->refreshTokens = new ArrayCollection();
        $this->creators = new ArrayCollection();
    }

    public static function createPro(): self
    {
        $user = new self();
        (new Membre())->setUser($user);

        return $user;
    }

    #[\Override]
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

    public function setEmail($email)
    {
        $this->email = $email;
        $this->emailCanonical = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTime $birthDate): self
    {
        $this->birthDate = $birthDate;
        $this->subjectBeneficiaire?->setDateNaissance($birthDate);

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
    public function setPrivateKey($privateKey): never
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
        return User::LANGUAGES[$this->lastLang] ?? self::DEFAULT_LANGUAGE;
    }

    public function setLastLang(?string $lastLang): self
    {
        $this->lastLang = $lastLang;

        return $this;
    }

    #[\Override]
    public function __toString(): string
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

    public function getSubjectBeneficiaire(): ?Beneficiaire
    {
        return $this->subjectBeneficiaire;
    }

    public function setSubjectBeneficiaire(?Beneficiaire $subjectBeneficiaire = null): self
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

    public function setSubjectGestionnaire(?Gestionnaire $subjectGestionnaire = null): self
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

    public function setSubjectAssociation(?Association $subjectAssociation = null): self
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

    public function setSubjectMembre(?Membre $subjectMembre = null): self
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

    public function setSubjectAdministrateur(?Administrateur $subjectAdministrateur = null): self
    {
        $this->subjectAdministrateur = $subjectAdministrateur;

        return $this;
    }

    public function isFirstVisit(): bool
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

    public function getPreviousLoginString(): ?string
    {
        return $this->lastLogin?->format('Y-m-d');
    }

    public function hasLoginToday(): bool
    {
        return $this->getPreviousLoginString() === (new \DateTime())->format('Y-m-d');
    }

    public function getDerniereConnexionAt(): ?\DateTime
    {
        return $this->derniereConnexionAt;
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
    #[\Override]
    public function jsonSerialize($withSubject = false): array
    {
        $data = [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'username' => $this->username,
            'type_user' => $this->typeUser,
            'email' => $this->email,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'last_login' => null !== $this->lastLogin ? $this->lastLogin->format(\DateTime::W3C) : null,
            'subject_id' => $this->getSubject()?->getId(),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'b_first_mobile_connexion' => $this->bFirstMobileConnexion,
            'adresse' => $this->getAdresse(),
            'centres' => $this->getUserRelays(),
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

    public function setAdresse(?Adresse $adresse = null): self
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
            'subject_id' => $this->getSubject()?->getId(),
            'last_login' => null !== $this->lastLogin ? $this->lastLogin->format(\DateTime::W3C) : null,
            'created_at' => $this->createdAt->format(\DateTime::W3C),
            'updated_at' => $this->updatedAt->format(\DateTime::W3C),
            'b_first_mobile_connexion' => $this->bFirstMobileConnexion,
            'adresse' => $this->getAdresse(),
        ];

        switch ($this->typeUser) {
            case self::USER_TYPE_BENEFICIAIRE:
                $data = array_merge($data, $this->subjectBeneficiaire->jsonSerializeAPI());
                break;
            case self::USER_TYPE_MEMBRE:
                $data = array_merge($data, $this->subjectMembre->jsonSerializeAPI());
                break;
            case self::USER_TYPE_GESTIONNAIRE:
                $data = array_merge($data, $this->subjectGestionnaire->jsonSerializeAPI());
                break;
            default:
                break;
        }

        return $data;
    }

    public function getCentresToString(?string $item = 'id'): string
    {
        $get = 'get'.ucfirst((string) $item);
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
        $subject = match ($this->getTypeUser()) {
            self::USER_TYPE_BENEFICIAIRE => $this->getSubjectBeneficiaire(),
            self::USER_TYPE_MEMBRE => $this->getSubjectMembre(),
            self::USER_TYPE_GESTIONNAIRE => $this->getSubjectGestionnaire(),
            default => null,
        };

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
        $creator = $this->creators?->filter(static fn ($creator) => $creator instanceof CreatorClient)->first();

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
            $this->creators = new ArrayCollection();
            if (null !== $this->subjectBeneficiaire || null !== $this->subjectMembre) {
                $creators = new ArrayCollection();
                if ($creatorUser = $this->getCreatorUser()) {
                    $creators->add(clone $creatorUser);
                }
                if ($creatorCentre = $this->getCreatorCentre()) {
                    $creators->add(clone $creatorCentre);
                }
                foreach ($creators as $creator) {
                    $this->addCreator($creator);
                }
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
        $creator = $this->creators?->filter(static fn ($creator) => $creator instanceof CreatorUser)->first();

        return false === $creator ? null : $creator;
    }

    public function getCreatorCentreRelay(): ?Centre
    {
        return $this->getCreatorCentre()?->getEntity();
    }

    public function getCreatorCentre(): ?CreatorCentre
    {
        $creator = $this->creators?->filter(static fn ($creator) => $creator instanceof CreatorCentre)->first();

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

    public function addCreatorUser(User $user): self
    {
        return $this->addCreator(new CreatorUser($user));
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

    public function setAutoLoginToken(?string $autoLoginToken = null): self
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

    public function setFcnToken(?string $fcnToken = null): self
    {
        $this->fcnToken = $fcnToken;

        return $this;
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

    public function getDefaultAdminUsername(): string
    {
        return sprintf('%s.admin', $this->getDefaultUsername());
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
        if (!$this->isBeneficiaire() && !$this->isMembre()) {
            return new ArrayCollection();
        }

        return $this->isBeneficiaire()
            ? $this->getSubjectBeneficiaire()->getBeneficiairesCentres()
            : $this->getSubjectMembre()->getMembresCentres();
    }

    public function getUserRelay(?Centre $relay): ?UserCentre
    {
        if (!$relay) {
            return null;
        }

        return $this->getUserRelays()
            ->filter(fn (UserCentre $userRelay) => $userRelay->getCentre() === $relay)
            ->first() ?: null;
    }

    public function getFirstUserRelay(): ?UserCentre
    {
        return $this->getUserRelays()->first() ?: null;
    }

    public static function createUserRelay(User $user, Centre $relay, bool $valid = false): UserCentre
    {
        $userRelay = $user->isBeneficiaire() ? new BeneficiaireCentre() : new MembreCentre();

        return $userRelay->setCentre($relay)->setUser($user)->setBValid($valid);
    }

    /** @return string[] */
    public function getRelaysIds(): array
    {
        return $this->relaysIds;
    }

    /** @param string[] $relaysIds */
    public function setRelaysIds(array $relaysIds): self
    {
        $this->relaysIds = $relaysIds;

        return $this;
    }

    public function getExternalRelayId(): ?string
    {
        return $this->externalRelayId;
    }

    public function setExternalRelayId(?string $relayId): self
    {
        $this->externalRelayId = $relayId;

        return $this;
    }

    public function getExternalProId(): ?string
    {
        return $this->externalProId;
    }

    public function setExternalProId(?string $proId): self
    {
        $this->externalProId = $proId;

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
        return !preg_match(self::BASE_USERNAME_REGEXP, (string) $this->username);
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

    public function getAffiliatedRelays(): Collection
    {
        if ($this->isBeneficiaire()) {
            return $this->getSubjectBeneficiaire()->getAffiliatedRelays();
        } elseif ($this->isMembre()) {
            return $this->getSubjectMembre()->getAffiliatedRelays();
        }

        return new ArrayCollection();
    }

    public function isLinkedToRelay(Centre $relay): bool
    {
        return null !== $this->getUserRelay($relay);
    }

    public function isInvitedToRelay(Centre $relay): bool
    {
        $userRelay = $this->getUserRelay($relay);

        return null !== $userRelay && !$userRelay->getBValid();
    }

    public function hasValidLinkToRelay(Centre $relay): bool
    {
        $userRelay = $this->getUserRelay($relay);

        return null !== $userRelay && $userRelay->getBValid();
    }

    public function hasPermissionOnRelay(Centre $relay, string $permission): bool
    {
        return $this->isMembre() && $this->getUserRelay($relay)?->hasDroit($permission);
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

    public function formatPhone(): static
    {
        if (preg_match('/^0[1-9][0-9]{8}$/', $this->telephone ?? '')) {
            $this->telephone = preg_replace('/^0/', '+33', (string) $this->telephone);
        }

        return $this;
    }

    public function usesRosalie(): bool
    {
        return $this->getSubjectMembre()?->usesRosalie() ?? false;
    }

    public function mustAcceptTermsOfUse(): bool
    {
        return !$this->hasRole('ROLE_ADMIN') && $this->isFirstVisit();
    }

    public function getCgsAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->cgsAcceptedAt;
    }

    public function setCgsAcceptedAt(?\DateTimeImmutable $cgsAcceptedAt): static
    {
        $this->cgsAcceptedAt = $cgsAcceptedAt;

        return $this;
    }

    public function acceptTermsOfUse(): static
    {
        $this->cgsAcceptedAt = new \DateTimeImmutable();

        return $this;
    }

    public function hasPasswordWithLatestPolicy(): bool
    {
        return $this->hasPasswordWithLatestPolicy;
    }

    public function setHasPasswordWithLatestPolicy(bool $hasUpdatedPasswordWithLatestPolicy): static
    {
        $this->hasPasswordWithLatestPolicy = $hasUpdatedPasswordWithLatestPolicy;

        return $this;
    }

    public function getPersonalAccountDataRequestedAt(): ?\DateTimeImmutable
    {
        return $this->personalAccountDataRequestedAt;
    }

    public function setPersonalAccountDataRequestedAt(?\DateTimeImmutable $personalAccountDataRequestedAt): void
    {
        $this->personalAccountDataRequestedAt = $personalAccountDataRequestedAt;
    }

    public function canRequestPersonalAccountData(): bool
    {
        return $this->isBeneficiaire() && ($this->telephone || $this->email);
    }

    public function hasRequestedPersonalAccountData(): bool
    {
        return (bool) $this->personalAccountDataRequestedAt;
    }

    #[\Override]
    public function isEmailAuthEnabled(): bool
    {
        return $this->isMfaEnabled() && self::MFA_METHOD_EMAIL === $this->mfaMethod;
    }

    #[\Override]
    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getAuthCode(): string
    {
        return $this->authCode ?? '';
    }

    #[\Override]
    public function getEmailAuthCode(): string
    {
        return $this->getAuthCode();
    }

    public function setAuthCode(string $authCode): void
    {
        $this->mfaCodeGeneratedAt = new \DateTimeImmutable();
        $this->authCode = $authCode;
    }

    #[\Override]
    public function setEmailAuthCode(string $authCode): void
    {
        $this->setAuthCode($authCode);
    }

    #[\Override]
    public function isTextAuthEnabled(): bool
    {
        return $this->isMfaEnabled() && self::MFA_METHOD_SMS === $this->mfaMethod;
    }

    #[\Override]
    public function getTextAuthRecipient(): string
    {
        return $this->email;
    }

    #[\Override]
    public function getTextAuthCode(): string
    {
        return $this->getAuthCode();
    }

    #[\Override]
    public function setTextAuthCode(string $authCode): void
    {
        $this->setAuthCode($authCode);
    }

    public function isMfaEnabled(): ?bool
    {
        return $this->mfaEnabled ?? false;
    }

    public function setMfaEnabled(bool $mfaEnabled = false): void
    {
        $this->mfaEnabled = $mfaEnabled;
    }

    public function enableMfa(): void
    {
        $this->mfaEnabled = true;
    }

    public function disableMfa(): void
    {
        $this->mfaEnabled = false;
    }

    public function isMfaPending(): ?bool
    {
        return $this->mfaPending;
    }

    public function setMfaPending(?bool $mfaPending): static
    {
        $this->mfaPending = $mfaPending;
        $this->mfaValid = !$mfaPending;

        return $this;
    }

    public function isMfaValid(): ?bool
    {
        return $this->mfaValid;
    }

    public function setMfaValid(?bool $mfaValid): static
    {
        $this->mfaValid = $mfaValid;
        $this->mfaPending = !$mfaValid;

        return $this;
    }

    public function resetAuthCodes(): static
    {
        $this->authCode = null;
        $this->mfaValid = false;
        $this->mfaPending = false;

        return $this;
    }

    public function getMfaMethod(): string
    {
        return $this->mfaMethod;
    }

    public function setMfaMethod(string $mfaMethod): static
    {
        $this->mfaMethod = $mfaMethod;

        return $this;
    }

    public function getMfaRetryCount(): ?int
    {
        return $this->mfaRetryCount;
    }

    public function setMfaRetryCount(?int $mfaRetryCount): static
    {
        $this->mfaRetryCount = $mfaRetryCount;

        return $this;
    }

    public function resetMfaRetryCount(): static
    {
        $this->mfaRetryCount = 0;

        return $this;
    }

    public function increaseMfaRetryCount(): static
    {
        if (null === $this->mfaRetryCount) {
            $this->mfaRetryCount = 0;
        }

        ++$this->mfaRetryCount;

        return $this;
    }

    public function sendMfaCode(): void
    {
        $this->increaseMfaRetryCount();
    }

    public function isMfaCodeCountLimitReach(): bool
    {
        return $this->mfaRetryCount >= self::MFA_MAX_SEND_CODE_COUNT;
    }

    public function getValidationGroup(): string
    {
        return $this->isBeneficiaire() ? 'beneficiaire' : 'membre';
    }

    public function getMfaCodeGeneratedAt(): ?\DateTimeInterface
    {
        return $this->mfaCodeGeneratedAt;
    }

    public function setMfaCodeGeneratedAt(?\DateTimeInterface $mfaCodeGeneratedAt): static
    {
        $this->mfaCodeGeneratedAt = $mfaCodeGeneratedAt;

        return $this;
    }

    public function isMfaCodeExpired(): bool
    {
        if (null === $this->mfaCodeGeneratedAt) {
            return true;
        }

        $expiration = $this->mfaCodeGeneratedAt->add(new \DateInterval('PT5M'));

        return $expiration < new \DateTimeImmutable();
    }

    public function isBeingCreated(): bool
    {
        if (!$this->subjectBeneficiaire) {
            return false;
        }

        return $this->subjectBeneficiaire->isCreating();
    }

    public function setSecretQuestion(?string $secretQuestion): self
    {
        $this->subjectBeneficiaire?->setQuestionSecrete($secretQuestion);

        return $this;
    }

    public function setSecretAnswer(?string $secretAnswer): self
    {
        $this->subjectBeneficiaire?->setReponseSecrete($secretAnswer);

        return $this;
    }
}
