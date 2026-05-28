<?php

namespace App\Controller\Admin;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class CalendarController extends AbstractController
{
    #[Route('/agenda', name: 'calendar')]
    public function calendar(): Response
    {
        return $this->render('admin/calendar.html.twig');
    }

    #[Route('/api/bookings/events', name: 'booking_events', methods: ['GET'])]
    public function bookingEvents(BookingRepository $repo, Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end   = $request->query->get('end');

        $qb = $repo->createQueryBuilder('b')
            ->join('b.client', 'c')
            ->leftJoin('b.animal', 'a')
            ->where('b.status IN (:statuses)')
            ->setParameter('statuses', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Pending])
            ->orderBy('b.scheduledAt', 'ASC');

        if ($start) {
            $startDt = new \DateTimeImmutable($start);
            $qb->andWhere('b.scheduledAt >= :start OR (b.scheduledAt IS NULL AND b.preferredDate >= :startDate)')
               ->setParameter('start', $startDt)
               ->setParameter('startDate', $startDt);
        }
        if ($end) {
            $endDt = new \DateTimeImmutable($end);
            $qb->andWhere('b.scheduledAt <= :end OR (b.scheduledAt IS NULL AND b.preferredDate <= :endDate)')
               ->setParameter('end', $endDt)
               ->setParameter('endDate', $endDt);
        }

        $bookings = $qb->getQuery()->getResult();

        $colorMap = [
            BookingStatus::Pending->value   => '#f59e0b',
            BookingStatus::Confirmed->value => '#10b981',
            BookingStatus::Completed->value => '#6b7280',
        ];

        $events = [];
        foreach ($bookings as $booking) {
            $start = $booking->getScheduledAt() ?? \DateTime::createFromFormat('Y-m-d', $booking->getPreferredDate()->format('Y-m-d'));
            if (!$start) {
                continue;
            }
            $end = $booking->getScheduledEndAt() ?? (clone $start)->modify('+1 hour');
            $client = $booking->getClient();
            $animal = $booking->getAnimal();

            $events[] = [
                'id'    => $booking->getId(),
                'title' => $client->getFirstName() . ' ' . ($client->getLastName() ?? '') . ' - ' . $booking->getServiceType()->label(),
                'start' => $start->format('c'),
                'end'   => $end->format('c'),
                'color' => $colorMap[$booking->getStatus()->value] ?? '#3b82f6',
                'extendedProps' => [
                    'bookingId'   => $booking->getId(),
                    'clientName'  => $client->getFirstName() . ' ' . ($client->getLastName() ?? ''),
                    'serviceName' => $booking->getServiceType()->label(),
                    'animalName'  => $animal?->getName(),
                    'price'       => $booking->getPrice() ? number_format((float) $booking->getPrice(), 2, ',', '') : null,
                    'adminNotes'  => $booking->getAdminNotes(),
                    'status'      => $booking->getStatus()->value,
                ],
            ];
        }

        return $this->json($events);
    }
}
