<?php

namespace App\Security;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TicketVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const CHANGE_STATUS = 'CHANGE_STATUS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CHANGE_STATUS], true)) {
            return false;
        }

        return $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($ticket, $user),
            self::EDIT => $this->canEdit($ticket, $user),
            self::DELETE => $this->canDelete($ticket, $user),
            self::CHANGE_STATUS => $this->canChangeStatus($ticket, $user),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        return $ticket->getUser() === $user || $ticket->getAffecteA() === $user;
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
        return $ticket->getUser() === $user;
    }

    private function canDelete(Ticket $ticket, User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    private function canChangeStatus(Ticket $ticket, User $user): bool
    {
        return $ticket->getAffecteA() === $user;
    }
}
