<?php

namespace App\Entity;

use App\Repository\ArticleViewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleViewRepository::class)]
#[ORM\UniqueConstraint(columns: ['article_id', 'ip_hash'])]
class ArticleView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'views')]
    #[ORM\JoinColumn(nullable: false)]
    private Article $article;

    #[ORM\Column(length: 64)]
    private string $ipHash = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getArticle(): Article { return $this->article; }
    public function setArticle(Article $article): static { $this->article = $article; return $this; }

    public function getIpHash(): string { return $this->ipHash; }
    public function setIpHash(string $ipHash): static { $this->ipHash = $ipHash; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
