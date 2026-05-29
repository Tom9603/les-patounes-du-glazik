<?php

namespace App\Repository;

use App\Entity\Availability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    /** @return Availability[] Upcoming blocks that have a reason, within $days days */
    public function findUpcomingWithReason(\DateTimeInterface $from, int $days = 90): array
    {
        $until = \DateTimeImmutable::createFromInterface($from)->modify("+{$days} days");

        return $this->createQueryBuilder('a')
            ->where('a.endAt > :from')
            ->andWhere('a.startAt < :until')
            ->andWhere('a.reason IS NOT NULL')
            ->andWhere("a.reason != ''")
            ->setParameter('from', $from)
            ->setParameter('until', $until)
            ->orderBy('a.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Availability[] */
    public function findOverlappingDay(\DateTimeInterface $date): array
    {
        $dayStart = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $dayEnd   = $dayStart->modify('+1 day');

        return $this->createQueryBuilder('a')
            ->where('a.startAt < :dayEnd AND a.endAt > :dayStart')
            ->setParameter('dayStart', $dayStart)
            ->setParameter('dayEnd', $dayEnd)
            ->getQuery()
            ->getResult();
    }
}
