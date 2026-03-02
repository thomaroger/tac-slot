<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reservation;
use App\Repository\AdherentRepository;
use App\Repository\ReservationRepository;
use App\Repository\SlotRepository;
use App\Util\FrenchDateFormatter;

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
        $cutoffStart = $todayStart;

        $todayStats = $this->buildWindowStats($todayStart->modify('-1 day'), $cutoffStart, false);
        $weekStats = $this->buildWindowStats($todayStart->modify('-7 days'), $cutoffStart, true);
        $monthStats = $this->buildWindowStats($todayStart->modify('-30 days'), $cutoffStart, false);
        $reservationStatusTrendMixed = $this->buildReservationStatusTrendBySlotDayWindow(
            $todayStart->modify('-14 days'),
            19
        );
        $seasonStart = $this->getSeasonStart($todayStart);
        $seasonEnd = $this->getSeasonEnd($seasonStart);
        $seasonStats = $this->buildWindowStats($seasonStart, $cutoffStart, false);
        $checkinOccupancyByDayAndHour = $this->buildCheckinOccupancyByDayAndHour($seasonStart, $cutoffStart);
        $bookingDelayByProfile = $this->buildBookingDelayByProfile($seasonStart, $seasonEnd);
        $lateCancellationStats = $this->buildLateCancellationStats($seasonStart, $todayStart);
        $noShowByWeekday = $this->buildNoShowByWeekdayStats($seasonStart, $todayStart);
        $forecastLoadNext4Days = $this->buildForecastLoadNextDays($todayStart, 4);
        $topReliableLimit = 10;
        $topReliableMinReservations = 5;
        $topReliableAdherents = $this->reservationRepository->findReliableAdherentsInWindow(
            $seasonStart,
            $todayStart,
            $topReliableMinReservations,
            $topReliableLimit
        );
        $noShowRiskMinReservations = 5;
        $topNoShowRiskAdherents = $this->reservationRepository->findAdherentsAtRiskNoShow(
            $seasonStart,
            $todayStart,
            $noShowRiskMinReservations,
            10
        );

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
            'slot_count_season' => $seasonStats['slot_count'],
            'reservation_count_season' => $seasonStats['reservation_count'],
            'reservation_confirmed_count_season' => $seasonStats['reservation_confirmed_count'],
            'reservation_cancelled_count_season' => $seasonStats['reservation_cancelled_count'],
            'reservation_noshow_count_season' => $seasonStats['reservation_noshow_count'],
            'reservation_count_adherent_season' => $seasonStats['reservation_count_adherent'],
            'tx_resa_season' => $seasonStats['tx_resa'],
            'top_reliable_adherents' => $topReliableAdherents,
            'top_reliable_limit' => $topReliableLimit,
            'top_reliable_min_reservations' => $topReliableMinReservations,
            'adherents_has_airkey' => $adherentsHasAirkey,
            'adherents_can_open_shoot' => $adherentsCanOpenShoot,
            'reservation_status_trend_mixed' => $reservationStatusTrendMixed,
            'checkin_occupancy_by_day_hour' => $checkinOccupancyByDayAndHour,
            'booking_delay_by_profile' => $bookingDelayByProfile,
            'late_cancellation_stats' => $lateCancellationStats,
            'no_show_by_weekday' => $noShowByWeekday,
            'forecast_load_next_4_days' => $forecastLoadNext4Days,
            'top_noshow_risk_adherents' => $topNoShowRiskAdherents,
            'noshow_risk_min_reservations' => $noShowRiskMinReservations,
            'season_start_label' => $seasonStart->format('d/m/Y'),
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
        $slots = $this->slotRepository->findSlotsInWindowWithReservations($start, $end);
        $slotCount = count($slots);
        $reservationCount = 0;
        $reservationConfirmedCount = 0;
        $reservationCancelledCount = 0;
        $reservationPendingCount = 0;
        $reservationNoShowCount = 0;
        $reservationUserIds = [];
        $maxPlaces = 0;
        $confirmedReservations = 0;
        $slotWithNumberOfReservation = [];

        foreach ($slots as $slot) {
            $maxPlaces += $slot->getMaxPlaces();

            foreach ($slot->getReservations() as $reservation) {
                $reservationCount++;
                $reservationUserIds[$reservation->getUser()->getId()] = true;

                switch ($reservation->getStatus()) {
                    case Reservation::STATUS_CONFIRMED:
                        $reservationConfirmedCount++;
                        $confirmedReservations++;
                        break;
                    case Reservation::STATUS_CANCELLED:
                        $reservationCancelledCount++;
                        break;
                    case Reservation::STATUS_PENDING:
                        $reservationPendingCount++;
                        break;
                    case Reservation::STATUS_NO_SHOW:
                        $reservationNoShowCount++;
                        break;
                }

                if ($withTopSlots) {
                    if ($reservation->getStatus() !== Reservation::STATUS_CONFIRMED) {
                        continue;
                    }
                    if (! isset($slotWithNumberOfReservation[$slot->toStringForTwig()])) {
                        $slotWithNumberOfReservation[$slot->toStringForTwig()] = 0;
                    }
                    $slotWithNumberOfReservation[$slot->toStringForTwig()]++;
                }
            }
        }

        $reservationCountAdherent = count($reservationUserIds);

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

    /**
     * @return array{labels: string[], datasets: array<string, int[]>}
     */
    private function buildReservationStatusTrendBySlotDayWindow(\DateTimeImmutable $windowStart, int $days): array
    {
        $windowEnd = $windowStart->modify('+' . $days . ' days');
        $slots = $this->slotRepository->findSlotsInWindowWithReservations($windowStart, $windowEnd);

        $trackedStatuses = [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_NO_SHOW,
        ];

        $labels = [];
        $fullLabels = [];
        $dateKeys = [];
        $datasets = [];

        foreach ($trackedStatuses as $status) {
            $datasets[$status] = [];
        }

        for ($i = 0; $i < $days; $i++) {
            $date = $windowStart->modify('+' . $i . ' days');
            $dateKey = $date->format('Y-m-d');
            $dateKeys[] = $dateKey;
            $labels[] = $date->format('d/m');
            $fullLabels[] = $date->format('d/m/Y');

            foreach ($trackedStatuses as $status) {
                $datasets[$status][$dateKey] = 0;
            }
        }

        foreach ($slots as $slot) {
            $slotDateKey = $slot->getStartAt()
                ->format('Y-m-d');
            if (! in_array($slotDateKey, $dateKeys, true)) {
                continue;
            }

            foreach ($slot->getReservations() as $reservation) {
                $status = $reservation->getStatus();
                if (! isset($datasets[$status][$slotDateKey])) {
                    continue;
                }

                $datasets[$status][$slotDateKey]++;
            }
        }

        $normalizedDatasets = [];
        foreach ($trackedStatuses as $status) {
            $normalizedDatasets[$status] = [];
            foreach ($dateKeys as $dateKey) {
                $normalizedDatasets[$status][] = $datasets[$status][$dateKey];
            }
        }

        return [
            'labels' => $labels,
            'full_labels' => $fullLabels,
            'datasets' => $normalizedDatasets,
            'today_index' => max(0, min($days - 1, 14)),
        ];
    }

    private function getSeasonStart(\DateTimeImmutable $todayStart): \DateTimeImmutable
    {
        $currentYear = (int) $todayStart->format('Y');
        $seasonStart = new \DateTimeImmutable(sprintf('%d-09-01 00:00:00', $currentYear));

        if ($todayStart < $seasonStart) {
            $seasonStart = $seasonStart->modify('-1 year');
        }

        return $seasonStart;
    }

    private function getSeasonEnd(\DateTimeImmutable $seasonStart): \DateTimeImmutable
    {
        return $seasonStart->modify('+1 year');
    }

    /**
     * @return array{
     *     days: array<int, array{key: int, label: string}>,
     *     hours: string[],
     *     cells: array<int, array<string, array{checkins: int, capacity: int, rate: float}>>
     * }
     */
    private function buildCheckinOccupancyByDayAndHour(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $slots = $this->slotRepository->findSlotsInWindowWithReservations($start, $end);

        $dayLabels = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        $raw = [];
        $hours = [];

        foreach ($slots as $slot) {
            $day = (int) $slot->getStartAt()
                ->format('N');
            $hour = $slot->getStartAt()
                ->format('H:i');
            $hours[$hour] = true;

            if (! isset($raw[$day][$hour])) {
                $raw[$day][$hour] = [
                    'checkins' => 0,
                    'capacity' => 0,
                ];
            }

            $raw[$day][$hour]['capacity'] += $slot->getMaxPlaces();

            foreach ($slot->getReservations() as $reservation) {
                if ($reservation->isCheckedIn()) {
                    $raw[$day][$hour]['checkins']++;
                }
            }
        }

        $hours = array_keys($hours);
        sort($hours);

        $days = [];
        $cells = [];

        foreach ($dayLabels as $dayKey => $dayLabel) {
            $days[] = [
                'key' => $dayKey,
                'label' => $dayLabel,
            ];
            $cells[$dayKey] = [];

            foreach ($hours as $hour) {
                $checkins = $raw[$dayKey][$hour]['checkins'] ?? 0;
                $capacity = $raw[$dayKey][$hour]['capacity'] ?? 0;
                $rate = $capacity > 0 ? ($checkins / $capacity) * 100 : 0;

                $cells[$dayKey][$hour] = [
                    'checkins' => $checkins,
                    'capacity' => $capacity,
                    'rate' => $rate,
                ];
            }
        }

        return [
            'days' => $days,
            'hours' => $hours,
            'cells' => $cells,
        ];
    }

    /**
     * @return array{
     *     buckets: array<int, string>,
     *     profiles: array<int, array{
     *         profile: string,
     *         reservations: int,
     *         avg_days: float,
     *         shares: array<string, float>,
     *         counts: array<string, int>
     *     }>
     * }
     */
    private function buildBookingDelayByProfile(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $reservations = $this->reservationRepository->findWithSlotAndUserBySlotStartWindow($start, $end);
        $bucketLabels = [
            'd0' => 'J+0',
            'd1' => 'J-1',
            'd2' => 'J-2',
            'd3' => 'J-3',
            'd4' => 'J-4',
        ];

        $stats = [];

        foreach ($reservations as $reservation) {
            if (! in_array(
                $reservation->getStatus(),
                [Reservation::STATUS_CONFIRMED, Reservation::STATUS_NO_SHOW],
                true
            )) {
                continue;
            }

            $profile = trim((string) $reservation->getUser()->getLevel());
            if ($profile === '') {
                $profile = 'Non renseigné';
            }

            if (! isset($stats[$profile])) {
                $stats[$profile] = [
                    'profile' => $profile,
                    'reservations' => 0,
                    'sum_days' => 0.0,
                    'counts' => array_fill_keys(array_keys($bucketLabels), 0),
                ];
            }

            $slotStart = $reservation->getSlot()
                ->getStartAt();
            $reservedAt = $reservation->getReservedAt();
            $delayDays = (int) floor(max(0, $slotStart->getTimestamp() - $reservedAt->getTimestamp()) / 86400);

            $bucket = 'd4';
            if ($delayDays <= 0) {
                $bucket = 'd0';
            } elseif ($delayDays === 1) {
                $bucket = 'd1';
            } elseif ($delayDays === 2) {
                $bucket = 'd2';
            } elseif ($delayDays === 3) {
                $bucket = 'd3';
            }

            $stats[$profile]['reservations']++;
            $stats[$profile]['sum_days'] += $delayDays;
            $stats[$profile]['counts'][$bucket]++;
        }

        foreach ($stats as &$profileStats) {
            $total = max(1, $profileStats['reservations']);
            $profileStats['avg_days'] = $profileStats['sum_days'] / $total;
            $shares = [];

            foreach ($profileStats['counts'] as $bucketKey => $count) {
                $shares[$bucketKey] = ($count / $total) * 100;
            }

            $profileStats['shares'] = $shares;
            unset($profileStats['sum_days']);
        }
        unset($profileStats);

        usort($stats, static fn (array $a, array $b): int => $b['reservations'] <=> $a['reservations']);

        return [
            'buckets' => array_values($bucketLabels),
            'profiles' => array_values($stats),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     late_count: int,
     *     late_rate: float,
     *     rows: array<int, array{label: string, count: int, share: float}>
     * }
     */
    private function buildLateCancellationStats(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $reservations = $this->reservationRepository->findCancelledWithSlotBySlotStartWindow($start, $end);
        $bucketLabels = [
            'lt24' => '< 24h',
            'h24_48' => '24h - 48h',
            'h48_72' => '48h - 72h',
            'd3_7' => '3j - 7j',
            'gt7d' => '> 7j',
        ];
        $counts = array_fill_keys(array_keys($bucketLabels), 0);
        $total = 0;
        $lateCount = 0;

        foreach ($reservations as $reservation) {
            $cancelledAt = $reservation->getCancelledAt();
            if (! $cancelledAt instanceof \DateTimeImmutable) {
                continue;
            }

            $slotStart = $reservation->getSlot()
                ->getStartAt();
            $delayHours = max(0, ($slotStart->getTimestamp() - $cancelledAt->getTimestamp()) / 3600);
            $total++;

            if ($delayHours < 24) {
                $counts['lt24']++;
                $lateCount++;
                continue;
            }

            if ($delayHours < 48) {
                $counts['h24_48']++;
                continue;
            }

            if ($delayHours < 72) {
                $counts['h48_72']++;
                continue;
            }

            if ($delayHours < 168) {
                $counts['d3_7']++;
                continue;
            }

            $counts['gt7d']++;
        }

        $rows = [];
        foreach ($bucketLabels as $key => $label) {
            $count = $counts[$key];
            $rows[] = [
                'label' => $label,
                'count' => $count,
                'share' => $total > 0 ? ($count / $total) * 100 : 0,
            ];
        }

        return [
            'total' => $total,
            'late_count' => $lateCount,
            'late_rate' => $total > 0 ? ($lateCount / $total) * 100 : 0,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     rows: array<int, array{label: string, count: int, share: float}>
     * }
     */
    private function buildNoShowByWeekdayStats(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $reservations = $this->reservationRepository->findNoShowWithSlotBySlotStartWindow($start, $end);
        $dayLabels = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        $counts = [];
        foreach ($dayLabels as $dayKey => $dayLabel) {
            $counts[$dayKey] = 0;
        }

        $total = 0;
        foreach ($reservations as $reservation) {
            $dayKey = (int) $reservation->getSlot()
                ->getStartAt()
                ->format('N');
            if (! isset($counts[$dayKey])) {
                continue;
            }

            $counts[$dayKey]++;
            $total++;
        }

        $rows = [];
        foreach ($dayLabels as $dayKey => $dayLabel) {
            $count = $counts[$dayKey];
            $rows[] = [
                'label' => $dayLabel,
                'count' => $count,
                'share' => $total > 0 ? ($count / $total) * 100 : 0,
            ];
        }

        return [
            'total' => $total,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array{date_label: string, reserved: int, capacity: int, rate: float, level: string}>
     */
    private function buildForecastLoadNextDays(\DateTimeImmutable $todayStart, int $days): array
    {
        $windowStart = $todayStart->modify('+1 day');
        $windowEnd = $windowStart->modify('+' . $days . ' days');
        $slots = $this->slotRepository->findSlotsInWindowWithReservations($windowStart, $windowEnd);

        $daily = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $windowStart->modify('+' . $i . ' days');
            $key = $date->format('Y-m-d');
            $daily[$key] = [
                'date_label' => FrenchDateFormatter::format($date, 'l d/m'),
                'reserved' => 0,
                'capacity' => 0,
                'rate' => 0.0,
                'level' => 'low',
            ];
        }

        foreach ($slots as $slot) {
            $key = $slot->getStartAt()
                ->format('Y-m-d');
            if (! isset($daily[$key])) {
                continue;
            }

            $daily[$key]['capacity'] += $slot->getMaxPlaces();
            $daily[$key]['reserved'] += $slot->getReservedPlaces();
        }

        foreach ($daily as &$row) {
            $row['rate'] = $row['capacity'] > 0 ? ($row['reserved'] / $row['capacity']) * 100 : 0.0;
            $row['level'] = match (true) {
                $row['rate'] >= 70 => 'high',
                $row['rate'] >= 35 => 'medium',
                default => 'low',
            };
        }
        unset($row);

        return array_values($daily);
    }
}
