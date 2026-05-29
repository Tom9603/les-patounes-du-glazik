<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GalleryController extends AbstractController
{
    #[Route('/galerie', name: 'app_gallery')]
    public function index(PhotoRepository $photoRepo): Response
    {
        $photos = $photoRepo->findAllOrdered();

        return $this->render('gallery/index.html.twig', [
            'photos' => $photos,
        ]);
    }
}
