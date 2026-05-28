<?php

namespace App\Controller;

use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/signer', name: 'app_signature_')]
class SignatureController extends AbstractController
{
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

        $invoice->setSignatureData($signatureData);
        $invoice->setSignedAt(new \DateTimeImmutable());
        $em->flush();

        // Notifier Sophie
        try {
            $mailer->send(
                (new TemplatedEmail())
                    ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
                    ->to($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
                    ->subject('Facture ' . $invoice->getNumber() . ' signée par ' . $invoice->getBooking()->getClient()->getFirstName())
                    ->htmlTemplate('emails/invoice_signed_admin.html.twig')
                    ->context(['invoice' => $invoice])
            );
        } catch (\Throwable) {}

        return $this->render('signature/confirmed.html.twig', ['invoice' => $invoice]);
    }
}
