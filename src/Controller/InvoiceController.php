<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use App\Repository\InvoiceRepository;
use App\Service\AuditLogger;
use App\Service\InvoicePdfService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/facture', name: 'app_invoice_')]
class InvoiceController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom,
        private AuditLogger $auditLogger,
        private LoggerInterface $logger,
    ) {}

    #[Route('/{id}', name: 'show')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(Invoice $invoice, StripeService $stripe): Response
    {
        if ($invoice->getBooking()->getClient() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('invoice/show.html.twig', [
            'invoice'   => $invoice,
            'publicKey' => $stripe->getPublicKey(),
        ]);
    }

    #[Route('/{id}/payer', name: 'pay', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function pay(Invoice $invoice, StripeService $stripe): JsonResponse
    {
        if ($invoice->getBooking()->getClient() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($invoice->isPaid()) {
            return $this->json(['error' => 'Facture déjà payée.'], 400);
        }

        $session = $stripe->createCheckoutSession($invoice);
        return $this->json(['url' => $session->url]);
    }

    #[Route('/{id}/confirmation', name: 'paid')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function paid(Invoice $invoice): Response
    {
        if ($invoice->getBooking()->getClient() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('invoice/paid.html.twig', ['invoice' => $invoice]);
    }

    #[Route('/{id}/pdf', name: 'pdf')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function pdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        if ($invoice->getBooking()->getClient() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $content = $pdfService->generate($invoice);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="facture-' . $invoice->getNumber() . '.pdf"');
        return $response;
    }

    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(
        Request $request,
        InvoiceRepository $invoiceRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        string $webhookSecret,
    ): JsonResponse {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException) {
            $this->logger->warning('stripe.webhook_signature_invalid', ['ip' => $request->getClientIp()]);
            return $this->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object, $invoiceRepo, $em, $mailer),
            'charge.refunded'            => $this->handleChargeRefunded($event->data->object, $invoiceRepo, $em, $mailer),
            default                      => null,
        };

        return $this->json(['ok' => true]);
    }

    private function handleCheckoutCompleted(object $session, InvoiceRepository $invoiceRepo, EntityManagerInterface $em, MailerInterface $mailer): void
    {
        $invoiceId = $session->metadata->invoice_id ?? null;
        if (!$invoiceId) {
            return;
        }

        $invoice = null;
        $em->beginTransaction();
        try {
            $invoice = $invoiceRepo->findWithLock((int) $invoiceId);
            if (!$invoice || $invoice->isPaid()) {
                $em->rollback();
                return;
            }

            $invoice->setStatus(InvoiceStatus::Paid);
            $invoice->setPaidAt(new \DateTimeImmutable());
            $invoice->setStripeCheckoutSessionId($session->id);
            $invoice->setStripePaymentIntentId($session->payment_intent);
            $em->flush();
            $em->commit();
        } catch (\Throwable $e) {
            $em->rollback();
            $this->logger->error('stripe.checkout_completed_failed', ['invoiceId' => $invoiceId, 'error' => $e->getMessage()]);
            return;
        }

        $this->auditLogger->invoicePaid($invoice);
        $this->sendPaymentConfirmedEmail($mailer, $invoice);
    }

    private function handleChargeRefunded(object $charge, InvoiceRepository $invoiceRepo, EntityManagerInterface $em, MailerInterface $mailer): void
    {
        $paymentIntentId = $charge->payment_intent ?? null;
        if (!$paymentIntentId) {
            return;
        }

        $invoice = null;
        $em->beginTransaction();
        try {
            $invoice = $invoiceRepo->findOneByStripeWithLock($paymentIntentId);
            if (!$invoice || $invoice->getStatus() === InvoiceStatus::Refunded) {
                $em->rollback();
                return;
            }

            $invoice->setStatus(InvoiceStatus::Refunded);
            $em->flush();
            $em->commit();
        } catch (\Throwable $e) {
            $em->rollback();
            $this->logger->error('stripe.charge_refunded_failed', ['paymentIntentId' => $paymentIntentId, 'error' => $e->getMessage()]);
            return;
        }

        $this->auditLogger->invoiceRefunded($invoice);
        $this->sendPaymentRefundedEmail($mailer, $invoice);
    }

    private function sendPaymentConfirmedEmail(MailerInterface $mailer, Invoice $invoice): void
    {
        $client = $invoice->getBooking()->getClient();
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($client->getEmail())
            ->subject('Paiement confirmé - Facture ' . $invoice->getNumber() . ' - Les patounes du glazik')
            ->html($this->renderView('emails/payment_confirmed.html.twig', ['invoice' => $invoice, 'member' => $client]));
        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Email client failed (payment confirmed)', ['invoice' => $invoice->getId(), 'error' => $e->getMessage()]);
        }
    }

    private function sendPaymentRefundedEmail(MailerInterface $mailer, Invoice $invoice): void
    {
        $client = $invoice->getBooking()->getClient();
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($client->getEmail())
            ->subject('Remboursement effectué - Facture ' . $invoice->getNumber() . ' - Les patounes du glazik')
            ->html($this->renderView('emails/payment_refunded.html.twig', ['invoice' => $invoice, 'member' => $client]));
        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Email client failed (payment refunded)', ['invoice' => $invoice->getId(), 'error' => $e->getMessage()]);
        }
    }
}
