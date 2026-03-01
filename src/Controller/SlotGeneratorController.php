<?php

namespace App\Controller;

use App\Service\SlotGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SlotGeneratorController extends AbstractController
{
    public function __construct(
        private readonly SlotGenerationService $slotGenerationService,
    ) {
    }

    #[Route('/generate-slots', name: 'generate_slots')]
    public function generate(): Response
    {
        $numberOfSlots = $this->slotGenerationService->generateSlotsForNext14Days();
        $this->addFlash('success', $numberOfSlots.' créneaux générés avec succès.');

        return $this->redirectToRoute('app_home');
    }
}
