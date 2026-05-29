<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap')]
    public function index(ArticleRepository $articleRepo): Response
    {
        $articles = $articleRepo->findPublished();

        $response = new Response(
            $this->renderView('sitemap.xml.twig', ['articles' => $articles]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );

        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}
