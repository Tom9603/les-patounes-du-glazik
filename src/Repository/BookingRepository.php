<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Member;
use App\Enum\BookingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findByMemberOrderedByDate(Member $member): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.client = :member')
            ->setParameter('member', $member)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Booking[] */
    public function findConfirmedOnDate(\DateTimeInterface $date): array
    {
        $dayStart = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $dayEnd   = $dayStart->modify('+1 day');

        return $this->createQueryBuilder('b')
            ->where('b.status = :status')
            ->andWhere('b.scheduledAt IS NOT NULL')
            ->andWhere('b.scheduledAt >= :dayStart AND b.scheduledAt < :dayEnd')
            ->setParameter('status', BookingStatus::Confirmed)
            ->setParameter('dayStart', $dayStart)
            ->setParameter('dayEnd', $dayEnd)
            ->getQuery()
            ->getResult();
    }

    public function hasActivBookings(Member $member): bool
    {
        $count = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.client = :member')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('statuses', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
