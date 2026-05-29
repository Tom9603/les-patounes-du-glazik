<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Invoice;
use App\Entity\Member;
use Psr\Log\LoggerInterface;

class AuditLogger
{
    public function __construct(private LoggerInterface $logger) {}

    public function bookingCreated(Booking $booking): void
    {
        $this->logger->info('booking.created', [
            'booking_id' => $booking->getId(),
            'client'     => $booking->getClient()->getEmail(),
            'service'    => $booking->getServiceType()->value,
            'date'       => $booking->getPreferredDate()->format('Y-m-d'),
        ]);
    }

    public function bookingStatusChanged(Booking $booking, string $oldStatus): void
    {
        $this->logger->info('booking.status_changed', [
            'booking_id' => $booking->getId(),
            'client'     => $booking->getClient()->getEmail(),
            'old_status' => $oldStatus,
            'new_status' => $booking->getStatus()->value,
        ]);
    }

    public function invoicePaid(Invoice $invoice): void
    {
        $this->logger->info('invoice.paid', [
            'invoice_id' => $invoice->getId(),
            'number'     => $invoice->getNumber(),
            'amount'     => $invoice->getAmount(),
            'client'     => $invoice->getBooking()->getClient()->getEmail(),
            'stripe_session' => $invoice->getStripeCheckoutSessionId(),
        ]);
    }

    public function invoiceRefunded(Invoice $invoice): void
    {
        $this->logger->info('invoice.refunded', [
            'invoice_id' => $invoice->getId(),
            'number'     => $invoice->getNumber(),
            'amount'     => $invoice->getAmount(),
            'client'     => $invoice->getBooking()->getClient()->getEmail(),
        ]);
    }

    public function invoiceSigned(Invoice $invoice): void
    {
        $this->logger->info('invoice.signed', [
            'invoice_id' => $invoice->getId(),
            'number'     => $invoice->getNumber(),
            'client'     => $invoice->getBooking()->getClient()->getEmail(),
            'signed_at'  => $invoice->getSignedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function memberDeleted(Member $member): void
    {
        $this->logger->warning('member.deleted', [
            'member_id' => $member->getId(),
            'email'     => $member->getEmail(),
            'bookings'  => $member->getBookings()->count(),
        ]);
    }

    public function securityEvent(string $event, array $context = []): void
    {
        $this->logger->warning('security.' . $event, $context);
    }
}
