<?php

namespace App\Repository;

use App\Entity\PromoCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromoCode>
 */
class PromoCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoCode::class);
    }

    public function findActiveByCode(string $code): ?PromoCode
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('p')
            ->andWhere('UPPER(p.code) = UPPER(:code)')
            ->andWhere('p.active = true')
            ->andWhere('(p.startAt IS NULL OR p.startAt <= :now)')
            ->andWhere('(p.endAt IS NULL OR p.endAt >= :now)')
            ->setParameter('code', $code)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
