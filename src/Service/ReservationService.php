<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function cleanExpiredPendingReservations(): int
    {
        $numberOfReservations = 0;
        $expiredReservations = $this->reservationRepository->findExpiredPendingReservations(
            new \DateTimeImmutable('-2 minutes')
        );

        foreach ($expiredReservations as $reservation) {
            $slot = $reservation->getSlot();
            $slot->setReservedPlaces(max(0, $slot->getReservedPlaces() - 1));
            $reservation->setStatus(Reservation::STATUS_CANCELLED);
            $reservation->setCancelledAt(new \DateTimeImmutable());
            $this->em->persist($reservation);
            $this->em->persist($slot);
            $numberOfReservations++;
        }

        $this->em->flush();

        return $numberOfReservations;
    }

    public function markNoShowReservations(): int
    {
        $numberOfNoShow = 0;
        $noShowReservations = $this->reservationRepository->findNoShowCandidates(new \DateTimeImmutable());

        foreach ($noShowReservations as $reservation) {
            $reservation->setStatus(Reservation::STATUS_NO_SHOW);
            $this->em->persist($reservation);
            $numberOfNoShow++;
        }

        $this->em->flush();

        return $numberOfNoShow;
    }

    public function checkIn(Reservation $reservation, Adherent $user): bool
    {
        if ($reservation->getUser() !== $user) {
            return false;
        }

        $reservation->setCheckedIn(true);
        $reservation->setCheckedInAt(new \DateTimeImmutable());

        $this->em->persist($reservation);
        $this->em->flush();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMyReservationsData(Adherent $user): array
    {
        $startOfDay = (new \DateTimeImmutable())->setTime(0, 0);
        $startOfTomorrow = $startOfDay->modify('+1 day');
        $currentReservations = $this->reservationRepository->findCurrentDayReservationsForUser(
            $user,
            $startOfDay,
            $startOfTomorrow
        );
        $futureReservations = $this->reservationRepository->findFutureReservationsForUser($user, $startOfTomorrow);
        $pastReservations = $this->reservationRepository->findPastReservationsForUser($user, $startOfDay);

        return [
            'user' => $user,
            'start_of_day' => $startOfDay,
            'current_reservations' => $this->sortReservationsByPriority($currentReservations),
            'futur_reservations' => $this->sortReservationsByPriority($futureReservations),
            'past_reservations' => $this->sortReservationsByPriority($pastReservations),
            'reservation_count_with_checkin' => $this->reservationRepository->countCheckedInForUser($user),
            'reservation_count_confirmed' => $this->reservationRepository->countByStatusForUser(
                $user,
                Reservation::STATUS_CONFIRMED
            ),
            'reservation_count_cancelled' => $this->reservationRepository->countByStatusForUser(
                $user,
                Reservation::STATUS_CANCELLED
            ),
            'reservation_count_noshow' => $this->reservationRepository->countByStatusForUser(
                $user,
                Reservation::STATUS_NO_SHOW
            ),
            'past_count_reservations_confirmed' => $this->reservationRepository->countPastConfirmedReservationsForUser(
                $user,
                $startOfDay
            ),
        ];
    }

    /**
     * @param Reservation[] $reservations
     *
     * @return Reservation[]
     */
    private function sortReservationsByPriority(array $reservations): array
    {
        $indexedReservations = [];
        foreach ($reservations as $index => $reservation) {
            $indexedReservations[] = [
                'index' => $index,
                'reservation' => $reservation,
            ];
        }

        usort(
            $indexedReservations,
            function (array $left, array $right): int {
                $leftPriority = $this->getReservationPriority($left['reservation']);
                $rightPriority = $this->getReservationPriority($right['reservation']);

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                return $left['index'] <=> $right['index'];
            }
        );

        return array_map(static fn (array $item): Reservation => $item['reservation'], $indexedReservations);
    }

    private function getReservationPriority(Reservation $reservation): int
    {
        if ($reservation->getStatus() === Reservation::STATUS_CONFIRMED && $reservation->isCheckedIn()) {
            return 0;
        }

        if ($reservation->getStatus() === Reservation::STATUS_NO_SHOW) {
            return 1;
        }

        if ($reservation->getStatus() === Reservation::STATUS_CONFIRMED) {
            return 2;
        }

        if ($reservation->getStatus() === Reservation::STATUS_CANCELLED) {
            return 3;
        }

        return 4;
    }
}
