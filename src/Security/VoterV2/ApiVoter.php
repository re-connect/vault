<?php

namespace App\Security\VoterV2;

use App\Entity\Interface\ClientResourceInterface;
use App\Security\HelperV2\Oauth2Helper;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiVoter extends Voter
{
    public const READ = 'READ';
    public const UPDATE = 'UPDATE';

    public function __construct(private readonly Oauth2Helper $oauth2Helper, private readonly AuthorizationCheckerInterface $checker)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::READ, self::UPDATE]) && $subject instanceof ClientResourceInterface;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token instanceof OAuth2Token) {
            return false; // This voter only handles api clients
        }

        return $this->hasRightScopeForOperation($attribute, $subject) && $this->oauth2Helper->canClientAccessRessource($token, $subject);
    }

    private function hasRightScopeForOperation(string $attribute, mixed $subject): bool
    {
        return $this->checker->isGranted(sprintf('ROLE_OAUTH2_%s_%s', $subject->getScopeName(), $attribute));
    }
}
