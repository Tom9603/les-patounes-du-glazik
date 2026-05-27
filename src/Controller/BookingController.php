<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Enum\ServiceType;
use App\Repository\AnimalRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reserver', name: 'app_booking_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class BookingController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.booking_per_hour')]
        private RateLimiterFactory $bookingPerHourLimiter,
    ) {}

    #[Route('', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        AnimalRepository $animalRepo,
        MailerInterface $mailer,
    ): Response {
        /** @var \App\Entity\Member $member */
        $member = $this->getUser();

        if (!$member->isVerified()) {
            $this->addFlash('error', 'Vous devez vérifier votre adresse email avant de pouvoir effectuer une réservation.');
            return $this->redirectToRoute('app_profile');
        }

        $animals = $animalRepo->findBy(['owner' => $member], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('booking-new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_booking_new');
            }

            $limiter = $this->bookingPerHourLimiter->create($member->getId() . '-booking');
            $limit = $limiter->consume(1);
            if (!$limit->isAccepted()) {
                $this->addFlash('error', 'Vous avez effectué trop de demandes de réservation. Veuillez patienter avant d\'en soumettre une nouvelle.');
                return $this->redirectToRoute('app_booking_new');
            }

            $serviceValue = $request->request->get('serviceType');
            $serviceType = ServiceType::tryFrom((string) $serviceValue);
            if ($serviceType === null) {
                $this->addFlash('error', 'Type de service invalide.');
                return $this->redirectToRoute('app_booking_new');
            }

            $preferredDateStr = $request->request->get('preferredDate');
            $preferredDate = \DateTime::createFromFormat('Y-m-d', (string) $preferredDateStr);
            if (!$preferredDate || $preferredDate < new \DateTime('today')) {
                $this->addFlash('error', 'La date souhaitée est invalide ou dans le passé.');
                return $this->redirectToRoute('app_booking_new');
            }

            $booking = new Booking();
            $booking->setClient($member);
            $booking->setServiceType($serviceType);
            $booking->setPreferredDate($preferredDate);
            $booking->setPreferredTime($request->request->get('preferredTime') ?: null);
            $booking->setAddress($request->request->get('address') ?: null);
            $booking->setClientNotes($request->request->get('clientNotes') ?: null);
            $booking->setPrice($serviceType->basePrice());

            $animalId = (int) $request->request->get('animalId');
            if ($animalId > 0) {
                $animal = $animalRepo->find($animalId);
                if ($animal && $animal->getOwner() === $member) {
                    $booking->setAnimal($animal);
                }
            }

            $em->persist($booking);
            $em->flush();

            $this->sendBookingReceivedEmails($mailer, $booking);

            $this->addFlash('success', 'Votre demande de réservation a bien été envoyée. Sophie reviendra vers vous prochainement pour confirmer.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('booking/new.html.twig', [
            'member' => $member,
            'animals' => $animals,
            'services' => ServiceType::cases(),
        ]);
    }

    private function sendBookingReceivedEmails(MailerInterface $mailer, Booking $booking): void
    {
        $client = $booking->getClient();

        $clientEmail = (new Email())
            ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
            ->to($client->getEmail())
            ->subject('Demande de réservation reçue - Les patounes du glazik')
            ->html($this->renderView('emails/booking_received_client.html.twig', ['booking' => $booking, 'member' => $client]));
        try { $mailer->send($clientEmail); } catch (\Throwable) {}

        $adminEmail = (new Email())
            ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
            ->to('sophielukomski.pro@gmail.com')
            ->subject('[Admin] Nouvelle demande de réservation - ' . $client->getFirstName())
            ->html($this->renderView('emails/booking_received_admin.html.twig', ['booking' => $booking, 'member' => $client]));
        try { $mailer->send($adminEmail); } catch (\Throwable) {}
    }
}
