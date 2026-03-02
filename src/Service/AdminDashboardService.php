<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reservation;
use App\Repository\AdherentRepository;
use App\Repository\ReservationRepository;
use App\Repository\SlotRepository;

class AdminDashboardService
{
    public function __construct(
        private readonly AdherentRepository $adherentRepository,
        private readonly SlotRepository $slotRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $adherentCount = $this->adherentRepository->countAll();
        $adherentActiveCount = $this->adherentRepository->countActive();
        $adherentDeletedCount = $this->adherentRepository->countSoftDeleted();
        $adherentsHasAirkey = $this->adherentRepository->countWithAirKey();
        $adherentsCanOpenShoot = $this->adherentRepository->countCanOpenShoot();

        $slotCount = $this->slotRepository->countAll();
        $reservationCount = $this->reservationRepository->countAllReservations();
        $reservationCountWithCheckin = $this->reservationRepository->countCheckedIn();
        $reservationCountConfirmed = $this->reservationRepository->countByStatus(Reservation::STATUS_CONFIRMED);
        $reservationCountCancelled = $this->reservationRepository->countByStatus(Reservation::STATUS_CANCELLED);
        $reservationCountNoshow = $this->reservationRepository->countByStatus(Reservation::STATUS_NO_SHOW);

        $todayStart = (new \DateTimeImmutable())->setTime(0, 0);
        $tomorrowStart = $todayStart->modify('+1 day');

        $todayStats = $this->buildWindowStats($todayStart, $tomorrowStart, false);
        $weekStats = $this->buildWindowStats($todayStart->modify('-7 days'), $tomorrowStart, true);
        $monthStats = $this->buildWindowStats($todayStart->modify('-30 days'), $tomorrowStart, false);

        return [
            'page_title' => 'Administration',
            'adherents_count' => $adherentCount,
            'adherents_active_count' => $adherentActiveCount,
            'adherents_delete_count' => $adherentDeletedCount,
            'slot_count' => $slotCount,
            'reservation_count' => $reservationCount,
            'reservation_count_with_checkin' => $reservationCountWithCheckin,
            'reservation_count_confirmed' => $reservationCountConfirmed,
            'reservation_count_cancelled' => $reservationCountCancelled,
            'reservation_count_noshow' => $reservationCountNoshow,
            'slot_count_today' => $todayStats['slot_count'],
            'reservation_count_today' => $todayStats['reservation_count'],
            'reservation_confirmed_count_today' => $todayStats['reservation_confirmed_count'],
            'reservation_cancelled_count_today' => $todayStats['reservation_cancelled_count'],
            'reservation_pending_count_today' => $todayStats['reservation_pending_count'],
            'reservation_noshow_count_today' => $todayStats['reservation_noshow_count'],
            'reservation_count_adherent_today' => $todayStats['reservation_count_adherent'],
            'tx_resa_today' => $todayStats['tx_resa'],
            'slot_count_7' => $weekStats['slot_count'],
            'reservation_count_7' => $weekStats['reservation_count'],
            'reservation_confirmed_count_7' => $weekStats['reservation_confirmed_count'],
            'reservation_cancelled_count_7' => $weekStats['reservation_cancelled_count'],
            'reservation_noshow_count_7' => $weekStats['reservation_noshow_count'],
            'reservation_count_adherent_7' => $weekStats['reservation_count_adherent'],
            'tx_resa_7' => $weekStats['tx_resa'],
            'slot_with_number_of_reservation_top5' => $weekStats['slot_with_number_of_reservation_top5'],
            'slot_count_30' => $monthStats['slot_count'],
            'reservation_count_30' => $monthStats['reservation_count'],
            'reservation_confirmed_count_30' => $monthStats['reservation_confirmed_count'],
            'reservation_cancelled_count_30' => $monthStats['reservation_cancelled_count'],
            'reservation_noshow_count_30' => $monthStats['reservation_noshow_count'],
            'reservation_count_adherent_30' => $monthStats['reservation_count_adherent'],
            'tx_resa_30' => $monthStats['tx_resa'],
            'top5_adherents' => $this->reservationRepository->findAdherentsByCheckinRatio(5, 'DESC'),
            'bad5_adherents' => $this->reservationRepository->findAdherentsByCheckinRatio(5, 'ASC'),
            'adherents_has_airkey' => $adherentsHasAirkey,
            'adherents_can_open_shoot' => $adherentsCanOpenShoot,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWindowStats(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        bool $withTopSlots
    ): array {
        $slotCount = $this->slotRepository->countByStartAtWindow($start, $end);
        $reservationCount = $this->reservationRepository->countReservedAtInWindow($start, $end);
        $reservationConfirmedCount = $this->reservationRepository->countReservedAtInWindow(
            $start,
            $end,
            Reservation::STATUS_CONFIRMED
        );
        $reservationCancelledCount = $this->reservationRepository->countCancelledAtInWindow($start, $end);
        $reservationPendingCount = $this->reservationRepository->countReservedAtInWindow(
            $start,
            $end,
            Reservation::STATUS_PENDING
        );
        $reservationNoShowCount = $this->reservationRepository->countReservedAtInWindow(
            $start,
            $end,
            Reservation::STATUS_NO_SHOW
        );
        $reservationCountAdherent = $this->reservationRepository->countDistinctAdherentsWithReservationsInWindow(
            $start,
            $end
        );

        $slots = $this->slotRepository->findSlotsInWindowWithReservations($start, $end);
        $maxPlaces = 0;
        $confirmedReservations = 0;
        $slotWithNumberOfReservation = [];

        foreach ($slots as $slot) {
            $maxPlaces += $slot->getMaxPlaces();

            foreach ($slot->getReservations() as $reservation) {
                if ($reservation->getStatus() !== Reservation::STATUS_CONFIRMED) {
                    continue;
                }

                $confirmedReservations++;

                if ($withTopSlots) {
                    if (! isset($slotWithNumberOfReservation[$slot->toStringForTwig()])) {
                        $slotWithNumberOfReservation[$slot->toStringForTwig()] = 0;
                    }
                    $slotWithNumberOfReservation[$slot->toStringForTwig()]++;
                }
            }
        }

        $txResa = $maxPlaces > 0 ? ($confirmedReservations / $maxPlaces) * 100 : 0;

        if ($withTopSlots) {
            arsort($slotWithNumberOfReservation);
            $slotWithNumberOfReservation = array_slice($slotWithNumberOfReservation, 0, 5, true);
        }

        return [
            'slot_count' => $slotCount,
            'reservation_count' => $reservationCount,
            'reservation_confirmed_count' => $reservationConfirmedCount,
            'reservation_cancelled_count' => $reservationCancelledCount,
            'reservation_pending_count' => $reservationPendingCount,
            'reservation_noshow_count' => $reservationNoShowCount,
            'reservation_count_adherent' => $reservationCountAdherent,
            'tx_resa' => $txResa,
            'slot_with_number_of_reservation_top5' => $slotWithNumberOfReservation,
        ];
    }
}
