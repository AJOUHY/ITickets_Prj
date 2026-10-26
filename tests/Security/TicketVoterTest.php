<?php

namespace App\Tests\Security;

use App\Entity\Ticket;
use App\Entity\User;
use App\Security\TicketVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TicketVoterTest extends TestCase
{
    private function createUser(array $roles): User
    {
        $user = new User();
        $user->setEmail('user' . uniqid() . '@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setPassword('secret');

        return $user;
    }

    private function createTicket(User $owner, ?User $assigned = null): Ticket
    {
        $ticket = new Ticket();
        $ticket->setUser($owner);
        $ticket->setAffecteA($assigned);

        return $ticket;
    }

    private function createToken(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    public function testClientCanViewOwnTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($client);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($client), $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $decision);
    }

    public function testClientCannotViewAnotherUsersTicket(): void
    {
        $clientA = $this->createUser(['ROLE_CLIENT']);
        $clientB = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($clientB);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($clientA), $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testTechnicianCanViewAssignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $ticket = $this->createTicket($client, $technician);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($technician), $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $decision);
    }

    public function testTechnicianCannotViewUnassignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $ticket = $this->createTicket($client);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($technician), $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testAdminCanViewAnyTicket(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $client = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($client);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($admin), $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $decision);
    }

    public function testTechnicianCanChangeStatusOnlyForAssignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $assignedTicket = $this->createTicket($client, $technician);
        $unassignedTicket = $this->createTicket($client);
        $voter = new TicketVoter();

        $assignedDecision = $voter->vote($this->createToken($technician), $assignedTicket, [TicketVoter::CHANGE_STATUS]);
        $unassignedDecision = $voter->vote($this->createToken($technician), $unassignedTicket, [TicketVoter::CHANGE_STATUS]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $assignedDecision);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $unassignedDecision);
    }

    public function testAdminCanDeleteTicket(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $client = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($client);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($admin), $ticket, [TicketVoter::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $decision);
    }

    public function testClientCannotDeleteTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($client);
        $voter = new TicketVoter();

        $decision = $voter->vote($this->createToken($client), $ticket, [TicketVoter::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }
}
