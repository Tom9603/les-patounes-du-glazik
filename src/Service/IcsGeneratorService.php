<?php

namespace App\Service;

use App\Entity\Booking;

class IcsGeneratorService
{
    public function generate(Booking $booking): string
    {
        $dtStart  = $booking->getScheduledAt() ?? new \DateTime();
        $dtEnd    = $booking->getScheduledEndAt() ?? (clone $dtStart)->modify('+1 hour');
        $client   = $booking->getClient();
        $animal   = $booking->getAnimal();
        $uid      = 'booking-' . $booking->getId() . '@lespatounesduglaizik.fr';
        $now      = new \DateTime('now', new \DateTimeZone('UTC'));

        $summary = 'Les patounes du glazik - ' . $booking->getServiceType()->label();
        if ($animal) {
            $summary .= ' (' . $animal->getName() . ')';
        }

        $description = implode('\\n', array_filter([
            'Service : ' . $booking->getServiceType()->label(),
            $animal ? 'Animal : ' . $animal->getName() : null,
            $booking->getPrice() ? 'Tarif : ' . number_format((float) $booking->getPrice(), 2, ',', '') . ' EUR' : null,
            $booking->getAdminNotes() ? 'Notes : ' . str_replace(["\r\n", "\n"], '\\n', $booking->getAdminNotes()) : null,
        ]));

        $address = $booking->getAddress() ?? '2 Kerbiguet, 29510 Landudal';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Les patounes du glazik//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $now->format('Ymd\THis\Z'),
            'DTSTART;TZID=Europe/Paris:' . $dtStart->format('Ymd\THis'),
            'DTEND;TZID=Europe/Paris:' . $dtEnd->format('Ymd\THis'),
            'SUMMARY:' . $this->icsEscape($summary),
            'DESCRIPTION:' . $description,
            'LOCATION:' . $this->icsEscape($address),
            'ORGANIZER;CN=Sophie Lukomski:mailto:' . ($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr'),
            'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=FALSE;CN=' . $client->getFirstName() . ':mailto:' . $client->getEmail(),
            'STATUS:CONFIRMED',
            'SEQUENCE:0',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    private function icsEscape(string $value): string
    {
        return str_replace([',', ';', '\\', "\n"], ['\\,', '\\;', '\\\\', '\\n'], $value);
    }
}
