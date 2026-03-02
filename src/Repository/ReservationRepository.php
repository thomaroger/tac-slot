<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Adherent;
use App\Entity\Reservation;
use App\Entity\Slot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function getReservationBySlotAndUser(
        Slot $slot,
        Adherent $user,
        string|array $status = Reservation::STATUS_PENDING
    ): ?Reservation {
        $qb = $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.slot = :slot')
            ->setParameter('user', $user)
            ->setParameter('slot', $slot)
            ->setMaxResults(1);

        if (is_array($status)) {
            $qb->andWhere('r.status IN (:status)')
                ->setParameter('status', $status);
        } else {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Reservation[]
     */
    public function findExpiredPendingReservations(\DateTimeImmutable $limitDate): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.reservedAt < :limit')
            ->setParameter('status', Reservation::STATUS_PENDING)
            ->setParameter('limit', $limitDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findNoShowCandidates(\DateTimeImmutable $limitDate): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.slot', 's')
            ->where('r.status = :status')
            ->andWhere('r.checkedIn = false')
            ->andWhere('s.endAt < :date')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->setParameter('date', $limitDate)
            ->getQuery()
            ->getResult();
    }

    public function findCurrentUncheckedConfirmedReservationForUser(
        Adherent $user,
        \DateTimeImmutable $now
    ): ?Reservation {
        return $this->createQueryBuilder('r')
            ->join('r.slot', 's')
            ->addSelect('s')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->andWhere('s.startAt <= :now')
            ->andWhere('s.endAt >= :now')
            ->andWhere('r.checkedIn = false')
            ->setParameter('user', $user)
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Reservation[]
     */
    public function findCurrentDayReservationsForUser(
        Adherent $user,
        \DateTimeImmutable $startOfDay,
        \DateTimeImmutable $startOfTomorrow
    ): array {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.slot', 's')
            ->addSelect('s')
            ->where('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->andWhere('r.user = :user')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $startOfTomorrow)
            ->setParameter('user', $user)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findFutureReservationsForUser(Adherent $user, \DateTimeImmutable $startOfTomorrow): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.slot', 's')
            ->addSelect('s')
            ->where('s.startAt > :start')
            ->andWhere('r.user = :user')
            ->setParameter('start', $startOfTomorrow)
            ->setParameter('user', $user)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findPastReservationsForUser(Adherent $user, \DateTimeImmutable $startOfDay): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.slot', 's')
            ->addSelect('s')
            ->where('s.startAt < :start')
            ->andWhere('r.user = :user')
            ->setParameter('start', $startOfDay)
            ->setParameter('user', $user)
            ->orderBy('s.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPastConfirmedReservationsForUser(Adherent $user, \DateTimeImmutable $startOfDay): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.slot', 's')
            ->where('s.startAt < :start')
            ->andWhere('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('start', $startOfDay)
            ->setParameter('user', $user)
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCheckedInForUser(Adherent $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.checkedIn = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatusForUser(Adherent $user, string $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllReservations(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCheckedIn(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.checkedIn = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countReservedAtInWindow(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        ?string $status = null
    ): int {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reservedAt >= :start')
            ->andWhere('r.reservedAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($status !== null) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countCancelledAtInWindow(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.cancelledAt >= :start')
            ->andWhere('r.cancelledAt < :end')
            ->andWhere('r.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', Reservation::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Reservation[]
     */
    public function findReservedAtInWindowWithUser(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->where('r.reservedAt >= :start')
            ->andWhere('r.reservedAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function countDistinctAdherentsWithReservationsInWindow(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): int {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT u.id)')
            ->join('r.user', 'u')
            ->where('r.reservedAt >= :start')
            ->andWhere('r.reservedAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAdherentsByCheckinRatio(int $limit = 5, string $order = 'DESC'): array
    {
        $safeOrder = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->select('u.id, u.firstName, u.lastName')
            ->addSelect('COUNT(r.id) AS totalReservations')
            ->addSelect('SUM(CASE WHEN r.checkedInAt IS NOT NULL THEN 1 ELSE 0 END) AS totalCheckins')
            ->addSelect('(SUM(CASE WHEN r.checkedInAt IS NOT NULL THEN 1 ELSE 0 END) * 1.0 / COUNT(r.id)) AS ratio')
            ->where('r.status = :status')
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->groupBy('u.id')
            ->having('COUNT(r.id) > 0')
            ->orderBy('ratio', $safeOrder)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
