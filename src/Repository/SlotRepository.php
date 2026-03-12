<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Slot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slot>
 */
class SlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slot::class);
    }

    public function hasAnySlotBetween(\DateTimeImmutable $end): bool
    {
        $slotId = $this->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.startAt >= :end')
            ->setParameter('end', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $slotId !== null;
    }

    /**
     * @return Slot[]
     */
    public function findReservableSlotsForPeriod(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        \DateTimeImmutable $reservationStart
    ): array {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.reservations', 'r')
            ->addSelect('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->where('s.endAt BETWEEN :start AND :end')
            ->andWhere('s.startAt <= :reservationStart')
            ->setParameter('start', value: $start)
            ->setParameter('end', $end)
            ->setParameter('reservationStart', $reservationStart)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentSlotWithReservations(\DateTimeImmutable $now): ?Slot
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.reservations', 'r')
            ->addSelect('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->andWhere('s.startAt <= :now')
            ->andWhere('s.endAt >= :now')
            ->setParameter('now', $now)
            ->orderBy('s.startAt', 'DESC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Slot[]
     */
    public function findSlotsInWindowWithReservations(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.reservations', 'r')
            ->addSelect('r')
            ->where('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function existsByStartAndEnd(\DateTimeImmutable $startAt, \DateTimeImmutable $endAt): bool
    {
        $slotId = $this->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.startAt = :startAt')
            ->andWhere('s.endAt = :endAt')
            ->setParameter('startAt', $startAt)
            ->setParameter('endAt', $endAt)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $slotId !== null;
    }

    public function countByStartAtWindow(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
