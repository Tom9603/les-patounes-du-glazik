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
        yield MenuItem::linkToRoute('Agenda', 'fa fa-calendar-days', 'app_admin_calendar');
        yield MenuItem::linkTo(BookingCrudController::class, 'Réservations', 'fa fa-calendar-check');
        yield MenuItem::linkToRoute('Factures', 'fa fa-file-invoice', 'app_admin_invoice_index');
        yield MenuItem::linkTo(AvailabilityCrudController::class, 'Disponibilités', 'fa fa-clock');
        yield MenuItem::linkTo(AnimalCrudController::class, 'Animaux', 'fa fa-paw');
        yield MenuItem::linkTo(HealthRecordCrudController::class, 'Fiches santé', 'fa fa-heart-pulse');

        yield MenuItem::section('Analytique');
        yield MenuItem::linkToRoute('Statistiques', 'fa fa-chart-line', 'app_admin_stats');

        yield MenuItem::section('Paramètres');
        yield MenuItem::linkTo(MemberCrudController::class, 'Utilisateurs', 'fa fa-users');

        yield MenuItem::section();
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-eye', '/');
    }
}
