<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\Reservation;
use App\Entity\Slot;
use App\Repository\ReservationRepository;
use App\Util\FrenchDateFormatter;
use Doctrine\ORM\EntityManagerInterface;

class SlotReservationService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{success: bool, message: string, flashType: string, flashMessage: string, remainingPlaces: int}
     */
    public function reserve(Slot $slot, Adherent $user, string $status): array
    {
        if ($slot->isClosed()) {
            return [
                'success' => false,
                'message' => 'creneau fermé',
                'flashType' => 'danger',
                'flashMessage' => 'Vous ne pouvez pas réserver un créneau qui est fermé',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ];
        }

        if ($slot->isFull() && $status === Reservation::STATUS_PENDING) {
            return [
                'success' => false,
                'message' => 'creneau plein',
                'flashType' => 'danger',
                'flashMessage' => 'Vous ne pouvez pas réserver un créneau qui est complet',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ];
        }

        if ($status === Reservation::STATUS_PENDING) {
            if ($slot->isReservedBy($user, Reservation::STATUS_PENDING)) {
                return [
                    'success' => false,
                    'message' => 'creneau déjà pré-réservé',
                    'flashType' => 'danger',
                    'flashMessage' => 'Vous ne pouvez pas réserver un créneau que vous avez déjà reservé',
                    'remainingPlaces' => $slot->getRemainingPlaces(),
                ];
            }

            $slot->setReservedPlaces($slot->getReservedPlaces() + 1);
            $this->em->persist($slot);

            $reservation = new Reservation();
            $reservation->setSlot($slot);
            $reservation->setUser($user);
            $reservation->setStatus(Reservation::STATUS_PENDING);
            $this->em->persist($reservation);
            $this->em->flush();

            return [
                'success' => true,
                'message' => 'ok',
                'flashType' => 'warning',
                'flashMessage' => 'Le créneau du ' . FrenchDateFormatter::format(
                    $slot->getStartAt(),
                    'l d F'
                ) . ' ' . $slot->getStartAt()->format('H:i') . ' - ' . $slot->getEndAt()->format(
                    'H:i'
                ) . ' a été pré-réservé',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ];
        }

        if ($status === Reservation::STATUS_CONFIRMED) {
            $reservation = $this->reservationRepository->getReservationBySlotAndUser($slot, $user);
            if (! $reservation instanceof Reservation) {
                return [
                    'success' => false,
                    'message' => 'reservation introuvable',
                    'flashType' => 'danger',
                    'flashMessage' => 'Impossible de confirmer cette réservation.',
                    'remainingPlaces' => $slot->getRemainingPlaces(),
                ];
            }

            $reservation->setStatus(Reservation::STATUS_CONFIRMED);
            $this->em->persist($reservation);
            $this->em->flush();

            return [
                'success' => true,
                'message' => 'ok',
                'flashType' => 'success',
                'flashMessage' => 'Le créneau du ' . FrenchDateFormatter::format(
                    $slot->getStartAt(),
                    'l d F'
                ) . ' ' . $slot->getStartAt()->format('H:i') . ' - ' . $slot->getEndAt()->format(
                    'H:i'
                ) . ' a été réservé',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ];
        }

        if ($status === Reservation::STATUS_CANCELLED) {
            $reservation = $this->reservationRepository->getReservationBySlotAndUser(
                $slot,
                $user,
                [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PENDING]
            );

            if (! $reservation instanceof Reservation) {
                return [
                    'success' => false,
                    'message' => 'reservation introuvable',
                    'flashType' => 'danger',
                    'flashMessage' => 'Impossible d\'annuler cette réservation.',
                    'remainingPlaces' => $slot->getRemainingPlaces(),
                ];
            }

            $slot->setReservedPlaces(max(0, $slot->getReservedPlaces() - 1));
            $reservation->setStatus(Reservation::STATUS_CANCELLED);
            $reservation->setCancelledAt(new \DateTimeImmutable());

            $this->em->persist($slot);
            $this->em->persist($reservation);
            $this->em->flush();

            return [
                'success' => true,
                'message' => 'ok',
                'flashType' => 'danger',
                'flashMessage' => 'La réservation du créneau du ' . FrenchDateFormatter::format(
                    $slot->getStartAt(),
                    'l d F'
                ) . ' ' . $slot->getStartAt()->format('H:i') . ' - ' . $slot->getEndAt()->format(
                    'H:i'
                ) . ' a été annulé',
                'remainingPlaces' => $slot->getRemainingPlaces(),
            ];
        }

        return [
            'success' => false,
            'message' => 'statut invalide',
            'flashType' => 'danger',
            'flashMessage' => 'Action invalide.',
            'remainingPlaces' => $slot->getRemainingPlaces(),
        ];
    }
}
