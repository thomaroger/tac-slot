<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\SlotRepository;

class HomeService
{
    public function __construct(
        private readonly SlotRepository $slotRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly ReservationService $reservationService,
    ) {
    }

    /**
     * @return array{redirectRoute: string|null, viewData: array<string, mixed>}
     */
    public function buildHomeData(Adherent $user): array
    {
        $today = new \DateTimeImmutable('today');
        $todayPlus4 = new \DateTimeImmutable('+4 days');
        $limit = $today->modify('+14 days');

        if (! $this->slotRepository->hasAnySlotBetween($todayPlus4, $limit)) {
            return [
                'redirectRoute' => 'generate_slots',
                'viewData' => [],
            ];
        }

        $expiredReservations = $this->reservationRepository->findExpiredPendingReservations(
            new \DateTimeImmutable('-2 minutes')
        );
        if (! empty($expiredReservations)) {
            $this->reservationService->cleanExpiredPendingReservations();
        }

        $noShowReservations = $this->reservationRepository->findNoShowCandidates(new \DateTimeImmutable());
        if (! empty($noShowReservations)) {
            $this->reservationService->markNoShowReservations();
        }

        $reservationStart = $this->getReservationDelay($user);
        $slots = $this->slotRepository->findReservableSlotsForPeriod($today, $limit, $reservationStart);

        $slotsByDay = [];
        foreach ($slots as $slot) {
            $dayKey = $slot->getStartAt()
                ->format('Y-m-d');
            if (! isset($slotsByDay[$dayKey])) {
                $slotsByDay[$dayKey] = [];
            }
            $slotsByDay[$dayKey][] = $slot;
        }

        $now = new \DateTimeImmutable();
        $slotnow = $this->reservationRepository->findCurrentUncheckedConfirmedReservationForUser($user, $now);
        $slot = $this->slotRepository->findCurrentSlotWithReservations($now);

        $slotReservations = [];
        $slotopened = false;
        $slotResa = 0;

        if ($slot !== null) {
            foreach ($slot->getReservations() as $reservation) {
                if ($reservation->isCheckedIn()) {
                    $slotReservations[] = $reservation;
                    $slotopened = true;
                }
                if ($reservation->getStatus() === Reservation::STATUS_CONFIRMED) {
                    $slotResa++;
                }
            }
        }

        return [
            'redirectRoute' => null,
            'viewData' => [
                'page_title' => 'Accueil',
                'slotsByDay' => $slotsByDay,
                'user' => $user,
                'slotnow' => $slotnow,
                'slot' => $slot,
                'slotopened' => $slotopened,
                'slotReservations' => $slotReservations,
                'slotResa' => $slotResa,
            ],
        ];
    }

    private function getReservationDelay(Adherent $adherent): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();

        return match ($adherent->getLevel()) {
            'National', 'Régional' => $now->modify('+4 days')
                ->setTime(0, 0),
            'Départemental' => $now->modify('+3 days')
                ->setTime(0, 0),
            'Débutant/Loisirs' => $now->modify('+2 days')
                ->setTime(0, 0),
            'Droit de Paille' => $now->modify('+1 day')
                ->setTime(0, 0),
            default => $now,
        };
    }
}
