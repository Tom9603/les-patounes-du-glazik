<?php

namespace App\Service;

use App\Entity\Invoice;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    private StripeClient $stripe;

    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private string $secretKey,
        #[Autowire('%env(STRIPE_PUBLIC_KEY)%')]
        private string $publicKey,
        private UrlGeneratorInterface $router,
    ) {
        $this->stripe = new StripeClient($secretKey);
    }

    public function createCheckoutSession(Invoice $invoice): Session
    {
        $booking = $invoice->getBooking();
        $amountCents = (int) round($invoice->getAmount() * 100);

        return $this->stripe->checkout->sessions->create([
            'mode'               => 'payment',
            'payment_method_types' => ['card'],
            'customer_email'     => $booking->getClient()->getEmail(),
            'line_items'         => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $amountCents,
                    'product_data' => [
                        'name' => 'Les patounes du glazik - ' . $booking->getServiceType()->label(),
                        'description' => 'Facture ' . $invoice->getNumber(),
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'invoice_id' => $invoice->getId(),
            ],
            'success_url' => $this->router->generate('app_invoice_paid', ['id' => $invoice->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url'  => $this->router->generate('app_invoice_show', ['id' => $invoice->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
