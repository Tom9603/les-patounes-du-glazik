<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ArticleView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleView>
 */
class ArticleViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleView::class);
    }

    public function hasViewed(Article $article, string $ipHash): bool
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.article = :article AND v.ipHash = :ipHash')
            ->setParameter('article', $article)
            ->setParameter('ipHash', $ipHash)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
