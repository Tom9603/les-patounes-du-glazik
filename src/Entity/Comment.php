<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private Article $article;

    #[ORM\Column(length: 100)]
    private string $authorName = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $authorEmail = null;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column]
    private bool $isApproved = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getArticle(): Article { return $this->article; }
    public function setArticle(Article $article): static { $this->article = $article; return $this; }

    public function getAuthorName(): string { return $this->authorName; }
    public function setAuthorName(string $authorName): static { $this->authorName = $authorName; return $this; }

    public function getAuthorEmail(): ?string { return $this->authorEmail; }
    public function setAuthorEmail(?string $authorEmail): static { $this->authorEmail = $authorEmail; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function isApproved(): bool { return $this->isApproved; }
    public function setIsApproved(bool $isApproved): static { $this->isApproved = $isApproved; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
