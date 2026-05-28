<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function nextNumber(): string
    {
        $year = date('Y');
        $count = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.number LIKE :prefix')
            ->setParameter('prefix', 'FAC-' . $year . '-%')
            ->getQuery()
            ->getSingleScalarResult();

        return sprintf('FAC-%s-%04d', $year, (int) $count + 1);
    }
}
