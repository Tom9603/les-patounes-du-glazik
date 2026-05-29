<?php

namespace App\Controller;

use App\Enum\ServiceType;
use App\Repository\AvailabilityRepository;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class BookingSlotController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(int:BOOKING_TRAVEL_BUFFER_MINUTES)%')]
        private int $travelBuffer,
    ) {}

    #[Route('/api/creneaux', name: 'app_api_slots', methods: ['GET'])]
    public function slots(
        Request $request,
        BookingRepository $bookingRepo,
        AvailabilityRepository $availRepo,
    ): JsonResponse {
        $dateStr = $request->query->get('date', '');
        $serviceValue = $request->query->get('service', '');

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
        if (!$date) {
            return $this->json(['error' => 'Date invalide'], 400);
        }

        $service = ServiceType::tryFrom($serviceValue);
        $durationMinutes = $service ? $service->durationMinutes() : 30;

        $confirmedBookings = $bookingRepo->findConfirmedOnDate($date);
        $blocks = $availRepo->findOverlappingDay($date);

        // Build blocked ranges: each confirmed booking + travel buffer after it
        $blockedRanges = [];
        foreach ($confirmedBookings as $booking) {
            if (!$booking->getScheduledAt()) continue;
            $bStart = \DateTimeImmutable::createFromInterface($booking->getScheduledAt());
            $bEnd = $booking->getScheduledEndAt()
                ? \DateTimeImmutable::createFromInterface($booking->getScheduledEndAt())
                : $bStart->modify('+' . $booking->getServiceType()->durationMinutes() . ' minutes');
            // Add travel buffer after the appointment
            $bEndWithTravel = $bEnd->modify("+{$this->travelBuffer} minutes");
            $blockedRanges[] = ['start' => $bStart, 'end' => $bEndWithTravel, 'type' => 'booking'];
        }

        foreach ($blocks as $block) {
            $bStart = $block->isAllDay() ? $date->setTime(0, 0) : $block->getStartAt();
            $bEnd   = $block->isAllDay() ? $date->setTime(23, 59) : $block->getEndAt();
            $blockedRanges[] = ['start' => $bStart, 'end' => $bEnd, 'type' => 'block'];
        }

        // 15-min steps for 45-min services, 30-min otherwise
        $stepMinutes = ($durationMinutes === 45) ? 15 : 30;

        // Pour aujourd'hui : bloquer les créneaux déjà passés (fuseau Paris, tampon 30 min)
        $paris = new \DateTimeZone('Europe/Paris');
        $nowParis = new \DateTimeImmutable('now', $paris);
        $isToday  = $date->format('Y-m-d') === $nowParis->format('Y-m-d');
        $minSlotStart = $isToday ? $nowParis->modify('+30 minutes') : null;

        // Generate slots 08:30 → 19:30
        $slots = [];
        $slotStart = $date->setTime(8, 30);
        $slotEnd   = $date->setTime(19, 30);

        while ($slotStart < $slotEnd) {
            $slotFinish = $slotStart->modify("+{$durationMinutes} minutes");
            $available = true;

            // Créneau dans le passé ou trop proche (aujourd'hui uniquement)
            if ($minSlotStart !== null && $slotStart < $minSlotStart) {
                $available = false;
            }

            if ($available) {
                foreach ($blockedRanges as $range) {
                    if ($slotStart < $range['end'] && $slotFinish > $range['start']) {
                        $available = false;
                        break;
                    }
                }
            }

            $slots[] = [
                'time'      => $slotStart->format('H:i'),
                'label'     => $slotStart->format('H:i'),
                'available' => $available,
            ];

            $slotStart = $slotStart->modify("+{$stepMinutes} minutes");
        }

        return $this->json(['slots' => $slots]);
    }
}
