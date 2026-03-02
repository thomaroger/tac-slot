<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuthLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthLog>
 */
class AuthLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthLog::class);
    }

    /**
     * @param string[] $events
     */
    public function countRecentByIpAndEvents(?string $ip, array $events, \DateTimeImmutable $since): int
    {
        $qb = $this->createQueryBuilder('al')
            ->select('COUNT(al.id)')
            ->where('al.createdAt >= :since')
            ->andWhere('al.event IN (:events)')
            ->setParameter('since', $since)
            ->setParameter('events', $events);

        if ($ip === null) {
            $qb->andWhere('al.ip IS NULL');
        } else {
            $qb->andWhere('al.ip = :ip')
                ->setParameter('ip', $ip);
        }

        return (int) $qb->getQuery()
            ->getSingleScalarResult();
    }
}
