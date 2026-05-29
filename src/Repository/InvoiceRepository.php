<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\LockMode;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findWithLock(int $id): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findOneByStripeWithLock(string $paymentIntentId): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.stripePaymentIntentId = :pi')
            ->setParameter('pi', $paymentIntentId)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function getMonthlyRevenue(\DateTimeInterface $from): array
    {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(amount) AS total
                FROM invoice
                WHERE status = 'paid' AND created_at >= :from
                GROUP BY month
                ORDER BY month ASC";

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, ['from' => (new \DateTimeImmutable('@' . $from->getTimestamp()))->format('Y-m-d H:i:s')])
            ->fetchAllAssociative();
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
