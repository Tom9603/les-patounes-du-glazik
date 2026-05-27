<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findPublished(?Category $category = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->where('a.isPublished = true')
            ->orderBy('a.publishedAt', 'DESC');

        if ($category) {
            $qb->andWhere('a.category = :category')->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')->addSelect('c')
            ->leftJoin('a.comments', 'co')->addSelect('co')
            ->where('a.slug = :slug AND a.isPublished = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
