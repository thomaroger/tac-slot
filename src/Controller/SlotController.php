<?php

namespace App\Controller;

use App\Entity\Adherent;
use App\Entity\Slot;
use App\Service\SlotReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SlotController extends AbstractController
{
    public function __construct(
        private readonly SlotReservationService $slotReservationService,
    ) {
    }

    #[Route('/slots/{id}/reserve/{status}', name: 'slot_reserve')]
    public function reserve(Slot $slot, string $status, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Adherent) {
            return $this->json([
                'success' => false,
                'message' => 'non authentifié',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ]);
        }

        $csrfToken = (string) $request->headers->get('X-CSRF-TOKEN', '');
        if (!$this->isCsrfTokenValid('slot_reserve', $csrfToken)) {
            return $this->json([
                'success' => false,
                'message' => 'csrf invalide',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ], 403);
        }

        $result = $this->slotReservationService->reserve($slot, $user, $status);
        $this->addFlash($result['flashType'], $result['flashMessage']);

        return $this->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'remainingPlaces' => $result['remainingPlaces'],
        ]);
    }
}
