<?php

namespace App\Entity;

use App\Enum\HealthRecordType;
use App\Repository\HealthRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HealthRecordRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HealthRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Animal::class, inversedBy: 'healthRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Animal $animal;

    #[ORM\Column(length: 30, enumType: HealthRecordType::class)]
    private HealthRecordType $type;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $recordedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextDueAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentFilename = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getAnimal(): Animal { return $this->animal; }
    public function setAnimal(Animal $animal): static { $this->animal = $animal; return $this; }

    public function getType(): HealthRecordType { return $this->type; }
    public function setType(HealthRecordType $type): static { $this->type = $type; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getRecordedAt(): ?\DateTimeImmutable { return $this->recordedAt; }
    public function setRecordedAt(?\DateTimeImmutable $d): static { $this->recordedAt = $d; return $this; }

    public function getNextDueAt(): ?\DateTimeImmutable { return $this->nextDueAt; }
    public function setNextDueAt(?\DateTimeImmutable $d): static { $this->nextDueAt = $d; return $this; }

    public function getAttachmentFilename(): ?string { return $this->attachmentFilename; }
    public function setAttachmentFilename(?string $f): static { $this->attachmentFilename = $f; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
