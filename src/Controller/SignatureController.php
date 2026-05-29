<?php

namespace App\Controller;

use App\Repository\InvoiceRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/signer', name: 'app_signature_')]
class SignatureController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom,
        #[Autowire('%env(ADMIN_EMAIL)%')]
        private string $adminEmail,
        private AuditLogger $auditLogger,
        private LoggerInterface $logger,
    ) {}

    #[Route('/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token, InvoiceRepository $repo): Response
    {
        $invoice = $repo->findOneBy(['signatureToken' => $token]);

        if (!$invoice) {
            throw $this->createNotFoundException('Lien de signature invalide.');
        }

        if ($invoice->isSigned()) {
            return $this->render('signature/already_signed.html.twig', ['invoice' => $invoice]);
        }

        return $this->render('signature/sign.html.twig', ['invoice' => $invoice]);
    }

    #[Route('/{token}', name: 'submit', methods: ['POST'])]
    public function submit(
        string $token,
        Request $request,
        InvoiceRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $invoice = $repo->findOneBy(['signatureToken' => $token]);

        if (!$invoice || $invoice->isSigned()) {
            throw $this->createNotFoundException();
        }

        $signatureData = $request->request->get('signature_data', '');

        if (!str_starts_with($signatureData, 'data:image/png;base64,')) {
            $this->addFlash('error', 'Signature invalide.');
            return $this->redirectToRoute('app_signature_show', ['token' => $token]);
        }

        // Limiter la taille du PNG base64 (max ~500 Ko)
        if (strlen($signatureData) > 700_000) {
            $this->addFlash('error', 'La signature est trop volumineuse.');
            return $this->redirectToRoute('app_signature_show', ['token' => $token]);
        }

        $invoice->setSignatureData($signatureData);
        $invoice->setSignedAt(new \DateTimeImmutable());
        $em->flush();

        $this->auditLogger->invoiceSigned($invoice);

        try {
            $mailer->send(
                (new TemplatedEmail())
                    ->from($this->mailerFrom)
                    ->to($this->adminEmail)
                    ->subject('Facture ' . $invoice->getNumber() . ' signée par ' . $invoice->getBooking()->getClient()->getFirstName())
                    ->htmlTemplate('emails/invoice_signed_admin.html.twig')
                    ->context(['invoice' => $invoice])
            );
        } catch (\Throwable $e) {
            $this->logger->error('Email admin failed (invoice signed)', ['invoice' => $invoice->getId(), 'error' => $e->getMessage()]);
        }

        return $this->render('signature/confirmed.html.twig', ['invoice' => $invoice]);
    }
}
