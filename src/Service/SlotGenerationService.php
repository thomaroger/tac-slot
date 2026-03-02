<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Slot;
use App\Repository\SlotRepository;
use Doctrine\ORM\EntityManagerInterface;

class SlotGenerationService
{
    public function __construct(
        private readonly SlotRepository $slotRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function generateSlotsForNext14Days(): int
    {
        $numberOfSlots = 0;
        $startDate = new \DateTimeImmutable();
        $endDate = $startDate->modify('+14 days');
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $dayOfWeek = (int) $currentDate->format('N');
            $timeRange = $this->getTimeRangeForDay($dayOfWeek);

            if ($timeRange !== null) {
                [$startHour, $endHour] = $timeRange;

                $startTime = new \DateTimeImmutable($currentDate->format('Y-m-d') . ' ' . $startHour);
                $endLimit = new \DateTimeImmutable($currentDate->format('Y-m-d') . ' ' . $endHour);

                while ($startTime < $endLimit) {
                    $slotEnd = $startTime->modify('+1 hour 30 minutes');
                    if ($slotEnd > $endLimit) {
                        $slotEnd = $endLimit;
                    }

                    if (! $this->slotRepository->existsByStartAndEnd($startTime, $slotEnd)) {
                        $slot = new Slot();
                        $slot->setStartAt($startTime)
                            ->setEndAt($slotEnd)
                            ->setMaxPlaces(12)
                            ->setReservedPlaces(0)
                            ->setIsClosed(false)
                            ->setRequiresAirKey(true);

                        $this->em->persist($slot);
                        $numberOfSlots++;
                    }

                    $startTime = $slotEnd;
                }
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        $this->em->flush();

        return $numberOfSlots;
    }

    private function getTimeRangeForDay(int $day): ?array
    {
        return match ($day) {
            1, 3 => ['13:30', '17:00'], // lundi, mercredi
            2, 5, 6 => ['13:30', '20:30'], // mardi, vendredi et samedi
            4 => ['13:30', '18:00'], // jeudi
            7 => ['09:00', '20:30'], // Dimanche
            default => null,
        };
    }
}
