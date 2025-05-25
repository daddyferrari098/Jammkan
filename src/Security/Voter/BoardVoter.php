<?php

namespace App\Security\Voter;

use App\Entity\Board;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class BoardVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Board;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        // Si l'utilisateur n'est pas connecté, on refuse l'accès
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Board $board */
        $board = $subject;

        // Vérifier si l'utilisateur est le propriétaire du tableau
        return match($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $board->getOwner() === $user,
            default => false,
        };
    }
}
