<?php

namespace App\Security\VoterV2;

use App\Entity\Interface\ClientResourceInterface;
use App\Security\HelperV2\Oauth2Helper;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiVoter extends Voter
{
    public const READ = 'READ';
    public const UPDATE = 'UPDATE';

    public function __construct(private readonly Oauth2Helper $oauth2Helper)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::READ, self::UPDATE]) && $subject instanceof ClientResourceInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token instanceof OAuth2Token) {
            return false; // This voter only handles api clients
        }

        return $this->oauth2Helper->canClientAccessRessource($token, $subject);
    }
}
