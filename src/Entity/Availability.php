<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column]
    private bool $allDay = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getStartAt(): \DateTimeImmutable { return $this->startAt; }
    public function setStartAt(\DateTimeImmutable $startAt): static { $this->startAt = $startAt; return $this; }

    public function getEndAt(): \DateTimeImmutable { return $this->endAt; }
    public function setEndAt(\DateTimeImmutable $endAt): static { $this->endAt = $endAt; return $this; }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): static { $this->reason = $reason; return $this; }

    public function isAllDay(): bool { return $this->allDay; }
    public function setAllDay(bool $allDay): static { $this->allDay = $allDay; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function __toString(): string
    {
        return ($this->reason ?? 'Indisponibilité') . ' (' . $this->startAt->format('d/m/Y') . ')';
    }
}
