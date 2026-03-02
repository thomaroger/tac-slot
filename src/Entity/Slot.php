<?php

declare(strict_types=1);

namespace App\Entity;

use App\Util\FrenchDateFormatter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Slot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(type: 'integer')]
    private int $maxPlaces = 12;

    #[ORM\Column(type: 'integer')]
    private int $reservedPlaces = 0;

    #[ORM\Column(type: 'integer')]
    private int $numberOfShow = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isClosed = false; // Fermé par admin

    #[ORM\Column(type: 'boolean')]
    private bool $requiresAirKey = true;

    #[ORM\OneToMany(mappedBy: 'slot', targetEntity: Reservation::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getId() . ' - ' . $this->getStartAt()->format('d/m/Y H:i') . ' - ' . $this->getEndAt()->format(
            'H:i'
        );
    }

    public function getRemainingPlaces(): int
    {
        return $this->maxPlaces - $this->reservedPlaces;
    }

    public function isFull(): bool
    {
        return $this->getRemainingPlaces() <= 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function getMaxPlaces(): int
    {
        return $this->maxPlaces;
    }

    public function getReservedPlaces(): int
    {
        return $this->reservedPlaces;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function setStartAt(\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function setEndAt(\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function setMaxPlaces(int $maxPlaces): self
    {
        $this->maxPlaces = $maxPlaces;

        return $this;
    }

    public function setReservedPlaces(int $reservedPlaces): self
    {
        $this->reservedPlaces = $reservedPlaces;

        return $this;
    }

    public function setIsClosed(bool $isClosed): self
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function setRequiresAirKey(bool $requiresAirKey): self
    {
        $this->requiresAirKey = $requiresAirKey;

        return $this;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (! $this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setSlot(slot: $this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            $reservation->setSlot(null);
        }

        return $this;
    }

    public function getRequiresAirKey(): bool
    {
        return $this->requiresAirKey;
    }

    public function getNumberOfShow(): int
    {
        return $this->numberOfShow;
    }

    public function setNumberOfShow(int $numberOfShow): self
    {
        $this->numberOfShow = $numberOfShow;

        return $this;
    }

    public function toStringForTwig(): string
    {
        return FrenchDateFormatter::format($this->getStartAt(), 'l d F H:i') . ' - ' . $this->getEndAt()->format('H:i');

    }

    public function hasReservationWithAirKey(): bool
    {
        foreach ($this->getReservations() as $reservation) {
            if ($reservation->getUser()->hasAirKey() and $reservation->getStatus() === Reservation::STATUS_CONFIRMED) {
                return true;
            }
        }
        return false;
    }

    public function isReservedBy(Adherent $user, $status = Reservation::STATUS_CONFIRMED): bool
    {
        foreach ($this->getReservations() as $reservation) {
            if ($status === Reservation::STATUS_CONFIRMED) {
                if (
                    $reservation->isReserved() &&
                    $reservation->getUser()
                        ->getId() === $user->getId()
                ) {
                    return true;
                }
            }
            if ($status === Reservation::STATUS_PENDING) {
                if (
                    $reservation->isPreReserved() &&
                    $reservation->getUser()
                        ->getId() === $user->getId()
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isReservedByWithCheckIn(Adherent $user, $status = Reservation::STATUS_CONFIRMED): bool
    {
        foreach ($this->getReservations() as $reservation) {
            if ($status === Reservation::STATUS_CONFIRMED) {
                if (
                    $reservation->isReserved() &&
                    $reservation->getUser()
                        ->getId() === $user->getId() &&
                    $reservation->isCheckedIn()
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canBeCancelled(): bool
    {
        $now = new \DateTimeImmutable();
        $diffInSeconds = $this->getStartAt()
            ->getTimestamp() - $now->getTimestamp();

        return $diffInSeconds > 3600;
    }
}
