<?php

namespace App\Command;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(name: 'app:send-email-reminders', description: 'Envoie les rappels email J-1 aux clients avec rendez-vous confirmé')]
class SendEmailRemindersCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private MailerInterface $mailer,
        private Environment $twig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tomorrow = new \DateTimeImmutable('tomorrow');
        $start = $tomorrow->setTime(0, 0, 0);
        $end   = $tomorrow->setTime(23, 59, 59);

        $bookings = $this->bookingRepo->createQueryBuilder('b')
            ->join('b.client', 'c')
            ->where('b.status = :status')
            ->andWhere('b.scheduledAt >= :start')
            ->andWhere('b.scheduledAt <= :end')
            ->setParameter('status', BookingStatus::Confirmed)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        if (empty($bookings)) {
            $io->info('Aucun rendez-vous confirmé demain.');
            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($bookings as $booking) {
            $client = $booking->getClient();

            try {
                $html = $this->twig->render('emails/booking_reminder.html.twig', [
                    'booking' => $booking,
                    'member'  => $client,
                ]);

                $email = (new Email())
                    ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
                    ->to($client->getEmail())
                    ->subject('Rappel : votre rendez-vous demain - Les patounes du glazik')
                    ->html($html);

                $this->mailer->send($email);
                $sent++;
                $io->writeln(sprintf('[OK] Rappel envoyé à %s (%s)', $client->getEmail(), $booking->getScheduledAt()->format('d/m H:i')));
            } catch (\Throwable $e) {
                $io->warning(sprintf('Echec pour %s : %s', $client->getEmail(), $e->getMessage()));
            }
        }

        $io->success(sprintf('Rappels envoyés : %d / %d', $sent, count($bookings)));
        return Command::SUCCESS;
    }
}
