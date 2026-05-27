<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Enum\ServiceType;
use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Member $client;

    #[ORM\ManyToOne(targetEntity: Animal::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Animal $animal = null;

    #[ORM\Column(enumType: ServiceType::class)]
    private ServiceType $serviceType;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private \DateTimeInterface $preferredDate;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $preferredTime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduledEndAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $address = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    private BookingStatus $status = BookingStatus::Pending;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $clientNotes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getClient(): Member { return $this->client; }
    public function setClient(Member $client): static { $this->client = $client; return $this; }

    public function getAnimal(): ?Animal { return $this->animal; }
    public function setAnimal(?Animal $animal): static { $this->animal = $animal; return $this; }

    public function getServiceType(): ServiceType { return $this->serviceType; }
    public function setServiceType(ServiceType $serviceType): static { $this->serviceType = $serviceType; return $this; }

    public function getPreferredDate(): \DateTimeInterface { return $this->preferredDate; }
    public function setPreferredDate(\DateTimeInterface $preferredDate): static { $this->preferredDate = $preferredDate; return $this; }

    public function getPreferredTime(): ?string { return $this->preferredTime; }
    public function setPreferredTime(?string $preferredTime): static { $this->preferredTime = $preferredTime; return $this; }

    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function setScheduledAt(?\DateTimeInterface $scheduledAt): static { $this->scheduledAt = $scheduledAt; return $this; }

    public function getScheduledEndAt(): ?\DateTimeInterface { return $this->scheduledEndAt; }
    public function setScheduledEndAt(?\DateTimeInterface $scheduledEndAt): static { $this->scheduledEndAt = $scheduledEndAt; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getPrice(): ?float
    {
        return $this->price !== null ? (float) $this->price : null;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price !== null ? (string) $price : null;
        return $this;
    }

    public function getStatus(): BookingStatus { return $this->status; }
    public function setStatus(BookingStatus $status): static { $this->status = $status; return $this; }

    public function getClientNotes(): ?string { return $this->clientNotes; }
    public function setClientNotes(?string $clientNotes): static { $this->clientNotes = $clientNotes; return $this; }

    public function getAdminNotes(): ?string { return $this->adminNotes; }
    public function setAdminNotes(?string $adminNotes): static { $this->adminNotes = $adminNotes; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function __toString(): string
    {
        return sprintf('Réservation #%d - %s', $this->id ?? 0, $this->serviceType->label());
    }
}
