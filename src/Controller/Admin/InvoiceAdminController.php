<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use App\Service\InvoicePdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/factures', name: 'app_admin_invoice_')]
#[IsGranted('ROLE_ADMIN')]
class InvoiceAdminController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(InvoiceRepository $repo): Response
    {
        $invoices = $repo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/invoice_index.html.twig', ['invoices' => $invoices]);
    }

    #[Route('/creer/{bookingId}/formulaire', name: 'create_form', methods: ['GET'])]
    public function invoiceCreateForm(int $bookingId, BookingRepository $bookingRepo): Response
    {
        $booking = $bookingRepo->find($bookingId);
        if (!$booking) {
            throw $this->createNotFoundException();
        }
        return $this->render('admin/invoice_create_form.html.twig', ['booking' => $booking]);
    }

    #[Route('/creer/{bookingId}', name: 'create', methods: ['POST'])]
    public function create(
        int $bookingId,
        Request $request,
        BookingRepository $bookingRepo,
        InvoiceRepository $invoiceRepo,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('invoice-create-' . $bookingId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin');
        }

        $booking = $bookingRepo->find($bookingId);
        if (!$booking) {
            throw $this->createNotFoundException();
        }

        $invoice = new Invoice();
        $invoice->setBooking($booking);
        $invoice->setNumber($invoiceRepo->nextNumber());
        $invoice->setAmount($booking->getPrice() ?? 0.0);
        $invoice->setStatus(InvoiceStatus::Sent);

        $em->persist($invoice);
        $em->flush();

        $this->addFlash('success', 'Facture ' . $invoice->getNumber() . ' créée.');
        return $this->redirectToRoute('app_admin_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(Invoice $invoice): Response
    {
        return $this->render('admin/invoice_show.html.twig', ['invoice' => $invoice]);
    }

    #[Route('/{id}/pdf', name: 'pdf')]
    public function pdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        $content = $pdfService->generate($invoice);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="facture-' . $invoice->getNumber() . '.pdf"');
        return $response;
    }

    #[Route('/{id}/demander-signature', name: 'request_signature', methods: ['POST'])]
    public function requestSignature(Invoice $invoice, Request $request, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('invoice-sign-' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($invoice->isSigned()) {
            $this->addFlash('error', 'Cette facture est déjà signée.');
            return $this->redirectToRoute('app_admin_invoice_show', ['id' => $invoice->getId()]);
        }

        $signUrl = $this->generateUrl('app_signature_show', ['token' => $invoice->getSignatureToken()], UrlGeneratorInterface::ABSOLUTE_URL);
        $client  = $invoice->getBooking()->getClient();

        try {
            $mailer->send(
                (new TemplatedEmail())
                    ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
                    ->to($client->getEmail())
                    ->subject('Bon pour accord - Facture ' . $invoice->getNumber() . ' - Les patounes du glazik')
                    ->htmlTemplate('emails/invoice_sign_request.html.twig')
                    ->context(['invoice' => $invoice, 'signUrl' => $signUrl])
            );
            $this->addFlash('success', 'Email de signature envoyé à ' . $client->getEmail() . '.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible d\'envoyer l\'email. Lien de signature : ' . $signUrl);
        }

        return $this->redirectToRoute('app_admin_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/marquer-payee', name: 'mark_paid', methods: ['POST'])]
    public function markPaid(Invoice $invoice, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('invoice-paid-' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_invoice_show', ['id' => $invoice->getId()]);
        }

        $invoice->setStatus(InvoiceStatus::Paid);
        $invoice->setPaidAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Facture marquée comme payée.');
        return $this->redirectToRoute('app_admin_invoice_show', ['id' => $invoice->getId()]);
    }
}
