<?php

namespace App\Controller;

use App\Entity\ArticleView;
use App\Entity\Comment;
use App\Entity\Member;
use App\Repository\ArticleRepository;
use App\Repository\ArticleViewRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    #[Route('', name: 'app_blog')]
    public function index(Request $request, ArticleRepository $articleRepo, CategoryRepository $categoryRepo): Response
    {
        $categorySlug = $request->query->get('categorie');
        $category = $categorySlug ? $categoryRepo->findOneBy(['slug' => $categorySlug]) : null;

        return $this->render('blog/index.html.twig', [
            'articles' => $articleRepo->findPublished($category),
            'categories' => $categoryRepo->findAll(),
            'currentCategory' => $category,
        ]);
    }

    #[Route('/{slug}', name: 'app_blog_show')]
    public function show(string $slug, ArticleRepository $articleRepo, ArticleViewRepository $viewRepo, EntityManagerInterface $em, Request $request): Response
    {
        $article = $articleRepo->findOnePublishedBySlug($slug);
        if (!$article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $ipHash = hash('sha256', $request->getClientIp() . $request->headers->get('User-Agent', ''));
            if (!$viewRepo->hasViewed($article, $ipHash)) {
                $view = new ArticleView();
                $view->setArticle($article);
                $view->setIpHash($ipHash);
                $em->persist($view);
                $em->flush();
            }
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{slug}/commenter', name: 'app_blog_comment', methods: ['POST'])]
    public function comment(string $slug, Request $request, ArticleRepository $articleRepo, EntityManagerInterface $em): Response
    {
        $article = $articleRepo->findOnePublishedBySlug($slug);
        if (!$article) {
            throw $this->createNotFoundException();
        }

        /** @var Member|null $member */
        $member = $this->getUser();

        if (!$member instanceof Member || !$member->isVerified()) {
            $this->addFlash('error', 'Vous devez être connecté avec un compte vérifié pour commenter.');
            return $this->redirectToRoute('app_blog_show', ['slug' => $slug]);
        }

        $content = trim($request->request->get('content', ''));

        if ($content) {
            $comment = new Comment();
            $comment->setArticle($article);
            $comment->setAuthorName($member->getUsername() ?? $member->getFirstName());
            $comment->setAuthorEmail($member->getEmail());
            $comment->setContent(htmlspecialchars($content));
            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été soumis et sera visible après modération.');
        }

        return $this->redirectToRoute('app_blog_show', ['slug' => $slug]);
    }
}
