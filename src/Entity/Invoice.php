<?php

namespace App\Entity;

use App\Enum\InvoiceStatus;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private Booking $booking;

    #[ORM\Column(length: 30, unique: true)]
    private string $number;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 20, enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::Draft;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getBooking(): Booking { return $this->booking; }
    public function setBooking(Booking $booking): static { $this->booking = $booking; return $this; }

    public function getNumber(): string { return $this->number; }
    public function setNumber(string $number): static { $this->number = $number; return $this; }

    public function getAmount(): float { return (float) $this->amount; }
    public function setAmount(float $amount): static { $this->amount = (string) $amount; return $this; }

    public function getStatus(): InvoiceStatus { return $this->status; }
    public function setStatus(InvoiceStatus $status): static { $this->status = $status; return $this; }

    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $id): static { $this->stripePaymentIntentId = $id; return $this; }

    public function getStripeCheckoutSessionId(): ?string { return $this->stripeCheckoutSessionId; }
    public function setStripeCheckoutSessionId(?string $id): static { $this->stripeCheckoutSessionId = $id; return $this; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $paidAt): static { $this->paidAt = $paidAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isPaid(): bool { return $this->status === InvoiceStatus::Paid; }
}
