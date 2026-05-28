<?php

namespace App\Command;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:send-sms-reminders', description: 'Envoie les rappels SMS J-1 aux clients')]
class SendSmsRemindersCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private SmsService $sms,
        private EntityManagerInterface $em,
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

        $sent = 0;
        foreach ($bookings as $booking) {
            $client = $booking->getClient();
            $phone  = $client->getPhone();
            if (!$phone) {
                continue;
            }

            $date = $booking->getScheduledAt()->format('d/m/Y à H:i');
            $message = "Bonjour {$client->getFirstName()} ! Rappel : votre prestation \"{$booking->getServiceType()->label()}\" est prévue demain {$date}. A demain ! - Sophie, Les patounes du glazik";

            if ($this->sms->send($phone, $message)) {
                $sent++;
                $io->success("SMS envoyé à {$client->getEmail()} ({$phone})");
            } else {
                $io->warning("Echec SMS pour {$client->getEmail()} ({$phone})");
            }
        }

        $io->info("Rappels envoyés : {$sent} / " . count($bookings));
        return Command::SUCCESS;
    }
}
