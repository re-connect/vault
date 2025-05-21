<?php

namespace App\Entity\Annotations;

use App\Entity\Attributes\User;
use App\RepositoryV2\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;

/**
 * @ORM\Entity(repositoryClass=ResetPasswordRequestRepository::class)
 *
 * @ORM\Table(name="reset_password_request")
 */
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    use ResetPasswordRequestTrait;

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime_immutable", name="expires_at")
     */
    protected $expiresAt;

    /**
     * @ORM\Column(type="datetime_immutable", name="requested_at")
     */
    protected $requestedAt;

    /**
     * @ORM\Column(type="string",length="100", name="hashed_token")
     */
    protected $hashedToken;

    /**
     * @ORM\Column(type="string", nullable="true")
     */
    private ?string $smsCode = null;

    /**
     * @ORM\Column(type="string", nullable="true")
     */
    private ?string $smsToken = null;

    public function __construct(/**
     * @ORM\ManyToOne(targetEntity=User::class)
     *
     * @ORM\JoinColumn(nullable=false)
     */
        private ?User $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[\Override]
    public function getUser(): object
    {
        return $this->user;
    }

    public function getSmsCode(): ?string
    {
        return $this->smsCode;
    }

    public function setSmsCode(string $smsCode): self
    {
        $this->smsCode = $smsCode;

        return $this;
    }

    public function getSmsToken(): ?string
    {
        return $this->smsToken;
    }

    public function setSmsToken(string $smsToken): self
    {
        $this->smsToken = $smsToken;

        return $this;
    }
}
