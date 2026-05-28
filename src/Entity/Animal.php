<?php

namespace App\Entity;

use App\Enum\AnimalSpecies;
use App\Enum\AnimalSex;
use App\Repository\AnimalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnimalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Animal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Member $owner;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(enumType: AnimalSpecies::class)]
    private AnimalSpecies $species = AnimalSpecies::Dog;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $breed = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(enumType: AnimalSex::class, nullable: true)]
    private ?AnimalSex $sex = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $color = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $microchip = null;

    #[ORM\Column]
    private bool $sterilized = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $healthNotes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'animal', targetEntity: HealthRecord::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['recordedAt' => 'DESC'])]
    private Collection $healthRecords;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->healthRecords = new ArrayCollection();
    }

    public function getHealthRecords(): Collection { return $this->healthRecords; }

    public function getId(): ?int { return $this->id; }

    public function getOwner(): Member { return $this->owner; }
    public function setOwner(Member $owner): static { $this->owner = $owner; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSpecies(): AnimalSpecies { return $this->species; }
    public function setSpecies(AnimalSpecies $species): static { $this->species = $species; return $this; }

    public function getBreed(): ?string { return $this->breed; }
    public function setBreed(?string $breed): static { $this->breed = $breed; return $this; }

    public function getBirthDate(): ?\DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $birthDate): static { $this->birthDate = $birthDate; return $this; }

    public function getSex(): ?AnimalSex { return $this->sex; }
    public function setSex(?AnimalSex $sex): static { $this->sex = $sex; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): static { $this->color = $color; return $this; }

    public function getMicrochip(): ?string { return $this->microchip; }
    public function setMicrochip(?string $microchip): static { $this->microchip = $microchip; return $this; }

    public function isSterilized(): bool { return $this->sterilized; }
    public function setSterilized(bool $sterilized): static { $this->sterilized = $sterilized; return $this; }

    public function getHealthNotes(): ?string { return $this->healthNotes; }
    public function setHealthNotes(?string $healthNotes): static { $this->healthNotes = $healthNotes; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function __toString(): string { return $this->name . ' (' . $this->species->label() . ')'; }
}
