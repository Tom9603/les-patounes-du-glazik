<?php

namespace App\Service;

use App\Entity\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class InvoicePdfService
{
    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function generate(Invoice $invoice): string
    {
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice,
            'booking' => $invoice->getBooking(),
            'client'  => $invoice->getBooking()->getClient(),
            'logoPath' => 'file://' . $this->projectDir . '/public/images/logo.png',
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
