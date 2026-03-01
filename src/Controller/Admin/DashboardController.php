<?php

namespace App\Controller\Admin;

use App\Entity\Adherent;
use App\Entity\AuthLog;
use App\Entity\Reservation;
use App\Entity\Slot;
use App\Service\AdminDashboardService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminDashboardService $adminDashboardService,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', $this->adminDashboardService->getDashboardData());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::section('Adhérents');
        yield MenuItem::linkToCrud('Adhérents', 'fa fa-users', Adherent::class);
        yield MenuItem::linkToRoute('Import adhérents', 'fa fa-file-import', 'admin_adherents_import');
        yield MenuItem::section('Gestion des créneaux');
        yield MenuItem::linkToCrud('Créneaux', 'fa fa-calendar-days', Slot::class);
        yield MenuItem::linkToCrud('Réservations', 'fa fa-calendar-check', Reservation::class);
        yield MenuItem::section('Security');
        yield MenuItem::linkToCrud('Monitoring', 'fa fa-eye', AuthLog::class);
        yield MenuItem::linkToUrl('Génération des créneaux', 'fa fa-add', '/generate-slots');
        yield MenuItem::linkToUrl('Nettoyage des créneaux non confirmés', 'fa fa-trash', '/reservations/clean');
        yield MenuItem::section('Navigation');
        yield MenuItem::linkToUrl('Frontend', 'fa fa-globe', '/');
        yield MenuItem::linkToUrl('Logout', 'fa-solid fa-arrow-right-to-bracket', '/logout');
    }
}
