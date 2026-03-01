<?php

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
        $expiredReservations = $this->reservationRepository->findExpiredPendingReservations(new \DateTimeImmutable('-2 minutes'));

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

        return [
            'user' => $user,
            'start_of_day' => $startOfDay,
            'current_reservations' => $this->reservationRepository->findCurrentDayReservationsForUser($user, $startOfDay, $startOfTomorrow),
            'futur_reservations' => $this->reservationRepository->findFutureReservationsForUser($user, $startOfTomorrow),
            'past_reservations' => $this->reservationRepository->findPastReservationsForUser($user, $startOfDay),
            'reservation_count_with_checkin' => $this->reservationRepository->countCheckedInForUser($user),
            'reservation_count_confirmed' => $this->reservationRepository->countByStatusForUser($user, Reservation::STATUS_CONFIRMED),
            'reservation_count_cancelled' => $this->reservationRepository->countByStatusForUser($user, Reservation::STATUS_CANCELLED),
            'reservation_count_noshow' => $this->reservationRepository->countByStatusForUser($user, Reservation::STATUS_NO_SHOW),
            'past_count_reservations_confirmed' => $this->reservationRepository->countPastConfirmedReservationsForUser($user, $startOfDay),
        ];
    }
}
