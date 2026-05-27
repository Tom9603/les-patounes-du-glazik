<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    private ?Category $category = null;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredImageFilename = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article', cascade: ['remove'])]
    private Collection $comments;

    #[ORM\OneToMany(targetEntity: ArticleView::class, mappedBy: 'article', cascade: ['remove'])]
    private Collection $views;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->views = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        if (empty($this->slug)) {
            $this->slug = strtolower((new AsciiSlugger('fr'))->slug($this->title));
        }
    }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getExcerpt(): ?string { return $this->excerpt; }
    public function setExcerpt(?string $excerpt): static { $this->excerpt = $excerpt; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    public function isPublished(): bool { return $this->isPublished; }
    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        if ($isPublished && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static { $this->publishedAt = $publishedAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getComments(): Collection { return $this->comments; }

    public function getApprovedComments(): Collection
    {
        return $this->comments->filter(fn(Comment $c) => $c->isApproved());
    }

    public function getViews(): Collection { return $this->views; }

    public function getViewCount(): int { return $this->views->count(); }

    public function getFeaturedImageFilename(): ?string { return $this->featuredImageFilename; }
    public function setFeaturedImageFilename(?string $featuredImageFilename): static { $this->featuredImageFilename = $featuredImageFilename; return $this; }

    public function __toString(): string { return $this->title; }
}
