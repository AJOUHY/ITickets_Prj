<?php

namespace App\Entity;

use App\Enum\TicketLieu;
use App\Enum\TicketStatus;
use App\Enum\TicketType;
use App\Enum\TicketUrgence;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: TicketType::class)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    private TicketType $type;

    #[ORM\ManyToOne(targetEntity: Categorie::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\Column(type: 'string', enumType: TicketUrgence::class)]
    #[Assert\NotBlank(message: 'L\'urgence est obligatoire.')]
    private TicketUrgence $urgence;

    #[ORM\Column(type: 'string', enumType: TicketLieu::class)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
    private TicketLieu $lieu;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateModification = null;

    #[ORM\Column(type: 'string', enumType: TicketStatus::class)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    private TicketStatus $status = TicketStatus::OPENED;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ticketsAffectes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $affecteA = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ticket', cascade: ['persist', 'remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->dateModification = new \DateTime();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): TicketType
    {
        return $this->type;
    }

    public function setType(TicketType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getUrgence(): TicketUrgence
    {
        return $this->urgence;
    }

    public function setUrgence(TicketUrgence $urgence): static
    {
        $this->urgence = $urgence;

        return $this;
    }

    public function getLieu(): TicketLieu
    {
        return $this->lieu;
    }

    public function setLieu(TicketLieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTime $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAffecteA(): ?User
    {
        return $this->affecteA;
    }

    public function setAffecteA(?User $affecteA): static
    {
        $this->affecteA = $affecteA;

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
            $comment->setTicket($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTicket() === $this) {
                $comment->setTicket(null);
            }
        }

        return $this;
    }
}
