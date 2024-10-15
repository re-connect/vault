<?php

namespace App\Security\Voter;

use App\Entity\Centre;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ClientVoter extends Voter
{
    public const string WRITE = 'WRITE';
    public const string DELETE = 'DELETE';

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::WRITE, self::DELETE])
            && $subject instanceof Centre;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::WRITE:
            case self::DELETE:
            default:
                break;
        }

        return false;
    }
}
