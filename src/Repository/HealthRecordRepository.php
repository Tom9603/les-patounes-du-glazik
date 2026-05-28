<?php

namespace App\Repository;

use App\Entity\HealthRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HealthRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthRecord::class);
    }

    public function findUpcomingReminders(\DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.animal', 'a')
            ->join('a.owner', 'o')
            ->where('h.nextDueAt IS NOT NULL')
            ->andWhere('h.nextDueAt <= :before')
            ->setParameter('before', $before)
            ->orderBy('h.nextDueAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
