<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Adherent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Adherent>
 */
class AdherentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adherent::class);
    }

    public function findOneByEmailOrLicense(string $identifier): ?Adherent
    {
        return $this->createQueryBuilder('a')
            ->where('a.email = :identifier')
            ->orWhere('a.licenseNumber = :identifier')
            ->setParameter('identifier', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmailVerificationToken(string $token): ?Adherent
    {
        return $this->createQueryBuilder('a')
            ->where('a.emailVerificationToken = :token')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByLicenseNumber(string $licenseNumber): ?Adherent
    {
        return $this->createQueryBuilder('a')
            ->where('a.licenseNumber = :licenseNumber')
            ->setParameter('licenseNumber', $licenseNumber)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.emailVerified = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSoftDeleted(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countWithAirKey(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.airKey = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCanOpenShoot(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.canOpenShoot = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
