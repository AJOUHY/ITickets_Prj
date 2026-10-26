<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $lastName = null;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_CLIENT'];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $tickets;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'affecteA', cascade: ['persist', 'remove'])]
    private Collection $ticketsAffectes;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $comments;

    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->tickets = new ArrayCollection();
        $this->ticketsAffectes = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique(array_filter($roles, static fn ($role) => is_string($role) && $role !== '')));
    }

    public function setRoles(array $roles): static
    {
        $normalizedRoles = [];

        foreach ($roles as $role) {
            if (is_string($role) && in_array($role, ['ROLE_ADMIN', 'ROLE_TECHNICIEN', 'ROLE_CLIENT'], true)) {
                $normalizedRoles[] = $role;
            }
        }

        $this->roles = $normalizedRoles ?: ['ROLE_CLIENT'];

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getRole(): string
    {
        return $this->roles[0] ?? 'ROLE_CLIENT';
    }

    public function setRole(string $role): static
    {
        return $this->setRoles([$role]);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setUser($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getUser() === $this) {
                $ticket->setUser(null);
            }
        }

        return $this;
    }

    public function getTicketsAffectes(): Collection
    {
        return $this->ticketsAffectes;
    }

    public function addTicketAffecte(Ticket $ticket): static
    {
        if (!$this->ticketsAffectes->contains($ticket)) {
            $this->ticketsAffectes[] = $ticket;
            $ticket->setAffecteA($this);
        }

        return $this;
    }

    public function removeTicketAffecte(Ticket $ticket): static
    {
        if ($this->ticketsAffectes->removeElement($ticket)) {
            if ($ticket->getAffecteA() === $this) {
                $ticket->setAffecteA(null);
            }
        }

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }
}
