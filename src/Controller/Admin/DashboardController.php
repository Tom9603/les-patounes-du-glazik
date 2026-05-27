<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Les patounes du glazik')
            ->setFaviconPath('images/logo.png');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Contenu');
        yield MenuItem::linkTo(PhotoCrudController::class, 'Galerie photos', 'fa fa-images');
        yield MenuItem::subMenu('Blog', 'fa fa-pen-to-square')->setSubItems([
            MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fa fa-file-lines'),
            MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-tags'),
            MenuItem::linkTo(CommentCrudController::class, 'Commentaires', 'fa fa-comments'),
        ]);

        yield MenuItem::section('Réservations');
        yield MenuItem::linkTo(BookingCrudController::class, 'Réservations', 'fa fa-calendar-check');
        yield MenuItem::linkTo(AnimalCrudController::class, 'Animaux', 'fa fa-paw');

        yield MenuItem::section('Paramètres');
        yield MenuItem::linkTo(MemberCrudController::class, 'Utilisateurs', 'fa fa-users');

        yield MenuItem::section();
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-eye', '/');
    }
}
