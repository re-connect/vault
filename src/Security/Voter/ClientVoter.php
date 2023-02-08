<?php

namespace App\Security\Voter;

use App\Entity\Centre;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ClientVoter extends Voter
{
    public const READ = 'READ';
    public const WRITE = 'WRITE';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::READ, self::WRITE, self::DELETE])
            && $subject instanceof Centre;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::WRITE:
            case self::DELETE:
            case self::READ:
                break;
        }

        return false;
    }
}
