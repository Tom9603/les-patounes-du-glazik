<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use App\Repository\InvoiceRepository;
use App\Service\InvoicePdfService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/facture', name: 'app_invoice_')]
class InvoiceController extends AbstractController
{
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
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        string $webhookSecret,
    ): JsonResponse {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException) {
            return $this->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session   = $event->data->object;
            $invoiceId = $session->metadata->invoice_id ?? null;
            if ($invoiceId) {
                $invoice = $invoiceRepo->find($invoiceId);
                if ($invoice && !$invoice->isPaid()) {
                    $invoice->setStatus(InvoiceStatus::Paid);
                    $invoice->setPaidAt(new \DateTimeImmutable());
                    $invoice->setStripeCheckoutSessionId($session->id);
                    $invoice->setStripePaymentIntentId($session->payment_intent);
                    $em->flush();
                }
            }
        }

        return $this->json(['ok' => true]);
    }
}
