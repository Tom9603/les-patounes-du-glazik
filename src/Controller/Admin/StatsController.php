<?php

namespace App\Controller\Admin;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use App\Repository\MemberRepository;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/statistiques', name: 'app_admin_stats')]
#[IsGranted('ROLE_ADMIN')]
class StatsController extends AbstractController
{
    public function __construct(private AdminContextProvider $adminContextProvider) {}

    #[Route('', name: '')]
    public function index(
        BookingRepository $bookingRepo,
        InvoiceRepository $invoiceRepo,
        MemberRepository $memberRepo,
    ): Response {
        if ($this->adminContextProvider->getContext() === null) {
            return $this->redirect('/admin?routeName=app_admin_stats');
        }
        $now = new \DateTimeImmutable();
        $yearStart = $now->modify('first day of January this year')->setTime(0, 0);

        // Revenue per month (current year)
        $monthlyRevenue = $invoiceRepo->getMonthlyRevenue($yearStart);

        $monthLabels = [];
        $monthValues = [];
        $frMonths = ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aou', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'];
        foreach ($monthlyRevenue as $row) {
            $monthNum = substr($row['month'], 5, 2);
            $monthLabels[] = $frMonths[$monthNum] ?? $row['month'];
            $monthValues[] = round((float) $row['total'], 2);
        }

        // Bookings by service type
        $bookingsByService = $bookingRepo->createQueryBuilder('b')
            ->select('b.serviceType as type, COUNT(b.id) as cnt')
            ->where('b.status NOT IN (:excluded)')
            ->setParameter('excluded', [BookingStatus::Refused, BookingStatus::Cancelled])
            ->groupBy('b.serviceType')
            ->getQuery()
            ->getArrayResult();

        $serviceLabels = [];
        $serviceValues = [];
        foreach ($bookingsByService as $row) {
            $serviceLabels[] = $row['type']->label();
            $serviceValues[] = (int) $row['cnt'];
        }

        // Summary stats
        $totalRevenue = $invoiceRepo->createQueryBuilder('i')
            ->select('SUM(i.amount)')
            ->where('i.status = :paid')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $totalBookings = $bookingRepo->count([]);
        $totalMembers  = $memberRepo->count([]);
        $pendingCount  = $bookingRepo->count(['status' => BookingStatus::Pending]);

        return $this->render('admin/stats.html.twig', [
            'revenueChart'  => ['labels' => $monthLabels, 'values' => $monthValues],
            'bookingsChart' => ['labels' => $serviceLabels, 'values' => $serviceValues],
            'totalRevenue'  => (float) $totalRevenue,
            'totalBookings' => $totalBookings,
            'totalMembers'  => $totalMembers,
            'pendingCount'  => $pendingCount,
        ]);
    }
}
