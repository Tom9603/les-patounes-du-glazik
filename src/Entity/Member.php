<?php

namespace App\Entity;

use App\Repository\MemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: MemberRepository::class)]
class Member implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetPasswordExpiresAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: \App\Entity\Animal::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $animals;

    #[ORM\OneToMany(targetEntity: \App\Entity\Booking::class, mappedBy: 'client', cascade: ['persist', 'remove'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->verificationToken = bin2hex(random_bytes(32));
        $this->animals = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(?string $username): static { $this->username = $username; return $this; }

    public function getDisplayName(): string
    {
        return $this->username ?? $this->firstName;
    }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        if (!in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_STAFF', $roles)) {
            $roles[] = 'ROLE_MEMBER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function eraseCredentials(): void {}

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        if ($isVerified) {
            $this->verificationToken = null;
        }
        return $this;
    }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): static { $this->verificationToken = $token; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getRoleLabel(): string
    {
        if (in_array('ROLE_ADMIN', $this->getRoles())) return 'Administrateur';
        if (in_array('ROLE_STAFF', $this->getRoles())) return 'Collaboratrice';
        return 'Utilisateur';
    }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getAvatarFilename(): ?string { return $this->avatarFilename; }
    public function setAvatarFilename(?string $avatarFilename): static { $this->avatarFilename = $avatarFilename; return $this; }

    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $googleId): static { $this->googleId = $googleId; return $this; }

    public function getFacebookId(): ?string { return $this->facebookId; }
    public function setFacebookId(?string $facebookId): static { $this->facebookId = $facebookId; return $this; }

    public function getResetPasswordToken(): ?string { return $this->resetPasswordToken; }
    public function setResetPasswordToken(?string $token): static { $this->resetPasswordToken = $token; return $this; }

    public function getResetPasswordExpiresAt(): ?\DateTimeImmutable { return $this->resetPasswordExpiresAt; }
    public function setResetPasswordExpiresAt(?\DateTimeImmutable $dt): static { $this->resetPasswordExpiresAt = $dt; return $this; }

    public function __toString(): string { return $this->firstName . ($this->lastName ? ' ' . $this->lastName : '') . ' (' . $this->email . ')'; }

    public function getAnimals(): Collection { return $this->animals; }
    public function addAnimal(\App\Entity\Animal $animal): static { if (!$this->animals->contains($animal)) { $this->animals->add($animal); $animal->setOwner($this); } return $this; }
    public function removeAnimal(\App\Entity\Animal $animal): static { $this->animals->removeElement($animal); return $this; }

    public function getBookings(): Collection { return $this->bookings; }
    public function addBooking(\App\Entity\Booking $booking): static { if (!$this->bookings->contains($booking)) { $this->bookings->add($booking); $booking->setClient($this); } return $this; }
    public function removeBooking(\App\Entity\Booking $booking): static { $this->bookings->removeElement($booking); return $this; }
}
