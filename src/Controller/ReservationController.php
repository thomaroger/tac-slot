<?php

namespace App\Controller;

use App\Entity\Adherent;
use App\Entity\Reservation;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {
    }

    #[Route('/reservations/clean', name: 'reservations_clean')]
    public function clean(Request $request): RedirectResponse
    {

        $numberOfReservations = $this->reservationService->cleanExpiredPendingReservations();
        $this->addFlash('warning', $numberOfReservations.' réservations perimées ont été supprimées.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/reservations/no-show', name: 'reservations_noshow')]
    public function noShow(Request $request): RedirectResponse
    {

        $numberOfNoShow = $this->reservationService->markNoShowReservations();
        $this->addFlash('warning', $numberOfNoShow.' réservations sans checkin mises à jour');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/reservations/{id}/check', name: 'reservation_check')]
    public function resaCheck(Reservation $reservation, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('reservation_check_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF token invalide.');
        }

        $user = $this->getUser();
        if (!$user instanceof Adherent) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->reservationService->checkIn($reservation, $user)) {
            $this->addFlash('danger', 'Vous ne pouvez pas checkin une reservation qui ne vous appartient pas');

            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('success', 'CheckIn fait pour la réservation du créneau '.$reservation->getSlot());

        return $this->redirectToRoute('app_home');
    }

    #[Route('/my-reservations/', name: 'reservations_my')]
    public function myReservations(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Adherent) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reservations/myreservartion.html.twig', $this->reservationService->getMyReservationsData($user));
    }
}
