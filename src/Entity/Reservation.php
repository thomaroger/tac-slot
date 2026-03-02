<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reservation
{
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Slot::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private Slot $slot;

    #[ORM\ManyToOne(targetEntity: Adherent::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Adherent $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_CONFIRMED;

    #[ORM\Column(type: 'boolean')]
    private bool $checkedIn = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $reservedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkedInAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct()
    {
        $this->reservedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlot(): Slot
    {
        return $this->slot;
    }

    public function setSlot(?Slot $slot): self
    {
        $this->slot = $slot;

        if ($slot && ! $slot->getReservations()->contains($this)) {
            $slot->addReservation($this);
        }

        return $this;
    }

    public function getUser(): Adherent
    {
        return $this->user;
    }

    public function setUser(Adherent $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isCheckedIn(): bool
    {
        return $this->checkedIn;
    }

    public function setCheckedIn(bool $checkedIn): self
    {
        $this->checkedIn = $checkedIn;

        return $this;
    }

    public function getReservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function setReservedAt(\DateTimeImmutable $reservedAt): self
    {
        $this->reservedAt = $reservedAt;

        return $this;
    }

    public function getCheckedInAt(): ?\DateTimeImmutable
    {
        return $this->checkedInAt;
    }

    public function setCheckedInAt(?\DateTimeImmutable $checkedInAt): self
    {
        $this->checkedInAt = $checkedInAt;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isNoShow(): bool
    {
        return $this->status === self::STATUS_NO_SHOW;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPreReserved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
