<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Service\AuditLogger;
use App\Service\IcsGeneratorService;
use App\Service\StripeService;
use App\Service\TravelTimeService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations', name: 'app_booking_admin_')]
#[IsGranted('ROLE_ADMIN')]
class BookingAdminController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom,
        private AuditLogger $auditLogger,
        private LoggerInterface $logger,
        private AdminContextProvider $adminContextProvider,
    ) {}

    #[Route('/{id}/confirmer/formulaire', name: 'confirm_form', methods: ['GET'])]
    public function confirmForm(
        Booking $booking,
        BookingRepository $bookingRepo,
        TravelTimeService $travelService,
    ): Response {
        if ($this->adminContextProvider->getContext() === null) {
            return $this->redirect('/admin?routeName=app_booking_admin_confirm_form&routeParams[id]=' . $booking->getId());
        }

        $sameDayBookings = $bookingRepo->findConfirmedOnDate($booking->getPreferredDate());

        // Build travel info: estimate from Sophie's base and between each confirmed booking
        $travelInfo = [];
        $currentAddress = $booking->getAddress();

        foreach ($sameDayBookings as $other) {
            $otherAddress = $other->getAddress();
            if (!$otherAddress) continue;

            $minutes = $currentAddress
                ? $travelService->estimateMinutes($otherAddress, $currentAddress)
                : null;

            $travelInfo[] = [
                'booking'    => $other,
                'minutes'    => $minutes,
                'sufficient' => $minutes === null || $minutes <= 30,
            ];
        }

        $fromBaseMinutes = $currentAddress ? $travelService->fromSophieBase($currentAddress) : null;

        return $this->render('admin/booking_confirm.html.twig', [
            'booking'         => $booking,
            'sameDayBookings' => $sameDayBookings,
            'travelInfo'      => $travelInfo,
            'fromBaseMinutes' => $fromBaseMinutes,
        ]);
    }

    #[Route('/{id}/refuser/formulaire', name: 'refuse_form', methods: ['GET'])]
    public function refuseForm(Booking $booking): Response
    {
        if ($this->adminContextProvider->getContext() === null) {
            return $this->redirect('/admin?routeName=app_booking_admin_refuse_form&routeParams[id]=' . $booking->getId());
        }

        return $this->render('admin/booking_refuse.html.twig', ['booking' => $booking]);
    }

    #[Route('/{id}/terminer/formulaire', name: 'complete_form', methods: ['GET'])]
    public function completeForm(Booking $booking): Response
    {
        if ($this->adminContextProvider->getContext() === null) {
            return $this->redirect('/admin?routeName=app_booking_admin_complete_form&routeParams[id]=' . $booking->getId());
        }

        return $this->render('admin/booking_complete.html.twig', ['booking' => $booking]);
    }

    #[Route('/{id}/confirmer', name: 'confirm', methods: ['POST'])]
    public function confirm(
        Booking $booking,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        IcsGeneratorService $ics,
        BookingRepository $bookingRepo,
    ): Response {
        if (!$this->isCsrfTokenValid('booking-confirm-' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        $scheduledAtStr    = $request->request->get('scheduledAt');
        $scheduledEndAtStr = $request->request->get('scheduledEndAt');
        $price             = $request->request->get('price');
        $adminNotes        = $request->request->get('adminNotes');

        $scheduledAt    = $scheduledAtStr    ? \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledAtStr)    : null;
        $scheduledEndAt = $scheduledEndAtStr ? \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledEndAtStr) : null;

        // Auto-calculate end time from service duration if not manually overridden
        if ($scheduledAt && !$scheduledEndAt) {
            $scheduledEndAt = (clone $scheduledAt)->modify('+' . $booking->getServiceType()->durationMinutes() . ' minutes');
        }

        // Alerte double-booking
        if ($scheduledAt) {
            $overlapping = $bookingRepo->findConfirmedOnDate($scheduledAt);
            $conflicts = array_filter($overlapping, fn(Booking $b) => $b->getId() !== $booking->getId());
            if (!empty($conflicts)) {
                $names = implode(', ', array_map(fn(Booking $b) => $b->getClient()->getFirstName() . ' (' . $b->getScheduledAt()?->format('H:i') . ')', $conflicts));
                $this->addFlash('warning', 'Attention : un ou plusieurs rendez-vous sont déjà confirmés ce jour. Verifiez les créneaux : ' . $names);
            }
        }

        $oldStatus = $booking->getStatus()->value;

        $booking->setStatus(BookingStatus::Confirmed);
        $booking->setScheduledAt($scheduledAt ?: null);
        $booking->setScheduledEndAt($scheduledEndAt ?: null);
        if ($price !== null && $price !== '') {
            $booking->setPrice((float) $price);
        }
        if ($adminNotes) {
            $booking->setAdminNotes($adminNotes);
        }

        $em->flush();

        $this->auditLogger->bookingStatusChanged($booking, $oldStatus);
        $this->sendConfirmationEmail($mailer, $booking, $ics);

        $this->addFlash('success', 'Réservation confirmée et email envoyé au client.');
        return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
    }

    #[Route('/{id}/refuser', name: 'refuse', methods: ['POST'])]
    public function refuse(Booking $booking, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('booking-refuse-' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        $oldStatus = $booking->getStatus()->value;
        $reason    = $request->request->get('reason', '');

        $booking->setStatus(BookingStatus::Refused);
        if ($reason) {
            $booking->setAdminNotes($reason);
        }

        $em->flush();

        $this->auditLogger->bookingStatusChanged($booking, $oldStatus);
        $this->sendRefusalEmail($mailer, $booking);

        $this->addFlash('success', 'Réservation refusée et email envoyé au client.');
        return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
    }

    #[Route('/{id}/terminer', name: 'complete', methods: ['POST'])]
    public function complete(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('booking-complete-' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        $oldStatus = $booking->getStatus()->value;
        $booking->setStatus(BookingStatus::Completed);
        $em->flush();

        $this->auditLogger->bookingStatusChanged($booking, $oldStatus);

        $this->addFlash('success', 'Réservation marquée comme terminée.');
        return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
    }

    #[Route('/{id}/annuler/formulaire', name: 'cancel_form', methods: ['GET'])]
    public function cancelForm(Booking $booking): Response
    {
        if ($this->adminContextProvider->getContext() === null) {
            return $this->redirect('/admin?routeName=app_booking_admin_cancel_form&routeParams[id]=' . $booking->getId());
        }

        return $this->render('admin/booking_cancel.html.twig', ['booking' => $booking]);
    }

    #[Route('/{id}/annuler', name: 'cancel', methods: ['POST'])]
    public function cancel(
        Booking $booking,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        StripeService $stripe,
    ): Response {
        if (!$this->isCsrfTokenValid('booking-cancel-' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        if (!in_array($booking->getStatus(), [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            $this->addFlash('error', 'Impossible d\'annuler une réservation avec ce statut.');
            return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
        }

        $oldStatus = $booking->getStatus()->value;
        $reason    = $request->request->get('reason', '');

        $booking->setStatus(BookingStatus::Cancelled);
        if ($reason) {
            $booking->setAdminNotes($reason);
        }
        $em->flush();

        $this->auditLogger->bookingStatusChanged($booking, $oldStatus);

        // Déclenche automatiquement le remboursement pour chaque facture payée
        $refundedCount = 0;
        foreach ($booking->getInvoices() as $invoice) {
            if ($invoice->isPaid() && $invoice->getStripePaymentIntentId()) {
                try {
                    $stripe->createRefund($invoice->getStripePaymentIntentId(), $invoice->getId());
                    $refundedCount++;
                } catch (\Throwable $e) {
                    $this->logger->error('Auto-refund failed on booking cancel', [
                        'booking' => $booking->getId(),
                        'invoice' => $invoice->getId(),
                        'error'   => $e->getMessage(),
                    ]);
                    $this->addFlash('warning', 'Réservation annulée, mais le remboursement Stripe a échoué pour la facture ' . $invoice->getNumber() . '. Remboursez manuellement depuis Stripe.');
                }
            }
        }

        $this->sendCancellationEmail($mailer, $booking);

        $msg = 'Réservation annulée. Client notifié par email.';
        if ($refundedCount > 0) {
            $msg .= ' Remboursement Stripe déclenché automatiquement.';
        }
        $this->addFlash('success', $msg);

        return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
    }

    private function sendConfirmationEmail(MailerInterface $mailer, Booking $booking, IcsGeneratorService $ics): void
    {
        $client     = $booking->getClient();
        $icsContent = $ics->generate($booking);
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($client->getEmail())
            ->subject('Votre réservation est confirmée - Les patounes du glazik')
            ->html($this->renderView('emails/booking_confirmed.html.twig', ['booking' => $booking, 'member' => $client]))
            ->addPart(new DataPart($icsContent, 'reservation.ics', 'text/calendar; method=REQUEST; charset=UTF-8'));
        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Email client failed (booking confirmed)', ['booking' => $booking->getId(), 'error' => $e->getMessage()]);
        }
    }

    private function sendRefusalEmail(MailerInterface $mailer, Booking $booking): void
    {
        $client = $booking->getClient();
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($client->getEmail())
            ->subject('Votre demande de réservation - Les patounes du glazik')
            ->html($this->renderView('emails/booking_refused.html.twig', ['booking' => $booking, 'member' => $client]));
        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Email client failed (booking refused)', ['booking' => $booking->getId(), 'error' => $e->getMessage()]);
        }
    }

    private function sendCancellationEmail(MailerInterface $mailer, Booking $booking): void
    {
        $client = $booking->getClient();
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($client->getEmail())
            ->subject('Réservation annulée - Les patounes du glazik')
            ->html($this->renderView('emails/booking_cancelled.html.twig', ['booking' => $booking, 'member' => $client]));
        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Email client failed (booking cancelled)', ['booking' => $booking->getId(), 'error' => $e->getMessage()]);
        }
    }
}
