<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Enum\AnimalSpecies;
use App\Enum\ServiceType;
use App\Repository\AnimalRepository;
use App\Repository\AvailabilityRepository;
use App\Repository\BookingRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom,
        #[Autowire('%env(ADMIN_EMAIL)%')]
        private string $adminEmail,
        private AuditLogger $auditLogger,
        private LoggerInterface $logger,
    ) {}

    #[Route('', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        AnimalRepository $animalRepo,
        AvailabilityRepository $availabilityRepo,
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
            if ($this->isGranted('ROLE_STAFF')) {
                $this->addFlash('error', 'Vous ne pouvez pas créer une réservation pour vous-même avec un compte administrateur.');
                return $this->redirectToRoute('app_booking_new');
            }

            if (!$this->isCsrfTokenValid('booking-new', $request->request->get('_token'))) {
                $this->auditLogger->securityEvent('csrf_failure', ['route' => 'booking_new', 'user' => $member->getEmail()]);
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_booking_new');
            }

            $limiter = $this->bookingPerHourLimiter->create($member->getId() . '-booking');
            $limit = $limiter->consume(1);
            if (!$limit->isAccepted()) {
                $this->auditLogger->securityEvent('rate_limit_exceeded', ['route' => 'booking_new', 'user' => $member->getEmail()]);
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
            $booking->setPrice($serviceType->basePrice());

            $animalId = (int) $request->request->get('animalId');
            if ($animalId > 0) {
                $animal = $animalRepo->find($animalId);
                if ($animal && $animal->getOwner() === $member) {
                    $booking->setAnimal($animal);
                    $booking->setAnimalSpecies($animal->getSpecies());
                }
            } else {
                $speciesValue = $request->request->get('animalSpecies');
                $booking->setAnimalSpecies($speciesValue ? AnimalSpecies::tryFrom($speciesValue) : null);
            }

            // Champ "préciser l'animal" (espèce Autre)
            $clientNotes = $request->request->get('clientNotes') ?: null;
            $otherSpecies = trim((string) $request->request->get('animalOtherSpecies', ''));
            if ($otherSpecies !== '' && $booking->getAnimalSpecies() === AnimalSpecies::Other) {
                $prefix = 'Animal : ' . $otherSpecies;
                $clientNotes = $clientNotes ? $prefix . "\n" . $clientNotes : $prefix;
            }
            $booking->setClientNotes($clientNotes);

            $em->persist($booking);
            $em->flush();

            $this->auditLogger->bookingCreated($booking);
            $this->sendBookingReceivedEmails($mailer, $booking);

            $this->addFlash('success', 'Votre demande de réservation a bien été envoyée. Sophie reviendra vers vous prochainement pour confirmer.');
            return $this->redirectToRoute('app_profile');
        }

        // Group client's animals by species value for JS
        $animalsBySpecies = [];
        foreach ($animals as $animal) {
            $animalsBySpecies[$animal->getSpecies()->value][] = [
                'id'   => $animal->getId(),
                'name' => $animal->getName(),
            ];
        }

        return $this->render('booking/new.html.twig', [
            'member'           => $member,
            'animals'          => $animals,
            'animalsBySpecies' => $animalsBySpecies,
            'speciesList'      => AnimalSpecies::cases(),
            'services'         => ServiceType::cases(),
            'upcomingBlocks'   => $availabilityRepo->findUpcomingWithReason(new \DateTimeImmutable()),
        ]);
    }

    private function sendBookingReceivedEmails(MailerInterface $mailer, Booking $booking): void
    {
        $client = $booking->getClient();

        $clientEmail = (new Email())
            ->from($this->mailerFrom)
            ->to($client->getEmail())
            ->subject('Demande de réservation reçue - Les patounes du glazik')
            ->html($this->renderView('emails/booking_received_client.html.twig', ['booking' => $booking, 'member' => $client]));
        try {
            $mailer->send($clientEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Email client failed (booking received)', ['booking' => $booking->getId(), 'error' => $e->getMessage()]);
        }

        $adminEmail = (new Email())
            ->from($this->mailerFrom)
            ->to($this->adminEmail)
            ->subject('[Admin] Nouvelle demande de réservation - ' . $client->getFirstName())
            ->html($this->renderView('emails/booking_received_admin.html.twig', ['booking' => $booking, 'member' => $client]));
        try {
            $mailer->send($adminEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Email admin failed (booking received)', ['booking' => $booking->getId(), 'error' => $e->getMessage()]);
        }
    }
}
