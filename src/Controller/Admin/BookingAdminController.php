<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Service\IcsGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    #[Route('/{id}/confirmer/formulaire', name: 'confirm_form', methods: ['GET'])]
    public function confirmForm(Booking $booking): Response
    {
        return $this->render('admin/booking_confirm.html.twig', ['booking' => $booking]);
    }

    #[Route('/{id}/refuser/formulaire', name: 'refuse_form', methods: ['GET'])]
    public function refuseForm(Booking $booking): Response
    {
        return $this->render('admin/booking_refuse.html.twig', ['booking' => $booking]);
    }

    #[Route('/{id}/terminer/formulaire', name: 'complete_form', methods: ['GET'])]
    public function completeForm(Booking $booking): Response
    {
        return $this->render('admin/booking_complete.html.twig', ['booking' => $booking]);
    }

    #[Route('/{id}/confirmer', name: 'confirm', methods: ['POST'])]
    public function confirm(Booking $booking, Request $request, EntityManagerInterface $em, MailerInterface $mailer, IcsGeneratorService $ics): Response
    {
        if (!$this->isCsrfTokenValid('booking-confirm-' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        $scheduledAtStr = $request->request->get('scheduledAt');
        $scheduledEndAtStr = $request->request->get('scheduledEndAt');
        $price = $request->request->get('price');
        $adminNotes = $request->request->get('adminNotes');

        $scheduledAt = $scheduledAtStr ? \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledAtStr) : null;
        $scheduledEndAt = $scheduledEndAtStr ? \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledEndAtStr) : null;

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

        $reason = $request->request->get('reason', '');
        $booking->setStatus(BookingStatus::Refused);
        if ($reason) {
            $booking->setAdminNotes($reason);
        }

        $em->flush();

        $this->sendRefusalEmail($mailer, $booking);

        $this->addFlash('success', 'Réservation refusée et email envoyé au client.');
        return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
    }

    #[Route('/{id}/terminer', name: 'complete', methods: ['POST'])]
    public function complete(Booking $booking, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('booking-complete-' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        $booking->setStatus(BookingStatus::Completed);
        $em->flush();

        $this->addFlash('success', 'Réservation marquée comme terminée.');
        return $this->redirectToRoute('admin', ['crudControllerFqcn' => BookingCrudController::class, 'crudAction' => 'index']);
    }

    private function sendConfirmationEmail(MailerInterface $mailer, Booking $booking, IcsGeneratorService $ics): void
    {
        $client = $booking->getClient();
        $icsContent = $ics->generate($booking);
        $email = (new Email())
            ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
            ->to($client->getEmail())
            ->subject('Votre réservation est confirmée - Les patounes du glazik')
            ->html($this->renderView('emails/booking_confirmed.html.twig', ['booking' => $booking, 'member' => $client]))
            ->addPart(new DataPart($icsContent, 'reservation.ics', 'text/calendar; method=REQUEST; charset=UTF-8'));
        try { $mailer->send($email); } catch (\Throwable) {}
    }

    private function sendRefusalEmail(MailerInterface $mailer, Booking $booking): void
    {
        $client = $booking->getClient();
        $email = (new Email())
            ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
            ->to($client->getEmail())
            ->subject('Votre demande de réservation - Les patounes du glazik')
            ->html($this->renderView('emails/booking_refused.html.twig', ['booking' => $booking, 'member' => $client]));
        try { $mailer->send($email); } catch (\Throwable) {}
    }
}
