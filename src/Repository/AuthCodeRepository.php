<?php

namespace App\Repository;

use App\Entity\AuthCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthCode>
 */
class AuthCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthCode::class);
    }

    /**
     * @return AuthCode[]
     */
    public function findLoginCodesForSessionAndAdherent(string $sessionId, \App\Entity\Adherent $adherent): array
    {
        return $this->createQueryBuilder('ac')
            ->where('ac.sessionId = :sessionId')
            ->andWhere('ac.adherent = :adherent')
            ->andWhere('ac.type = :type')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('adherent', $adherent)
            ->setParameter('type', 'login')
            ->getQuery()
            ->getResult();
    }

    public function findOneLoginCodeForSession(string $sessionId, \App\Entity\Adherent $adherent, string $code): ?AuthCode
    {
        return $this->createQueryBuilder('ac')
            ->where('ac.sessionId = :sessionId')
            ->andWhere('ac.adherent = :adherent')
            ->andWhere('ac.code = :code')
            ->andWhere('ac.type = :type')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('adherent', $adherent)
            ->setParameter('code', $code)
            ->setParameter('type', 'login')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
