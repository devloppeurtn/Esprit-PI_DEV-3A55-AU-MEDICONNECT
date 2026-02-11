<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * @return Participant[]
     */
    public function findByEvenement($evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :m')
            ->setParameter('m', $evenementId)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Backwards compatibility wrapper
     * @return Participant[]
     */
    public function findByModuleFour($moduleFourId): array
    {
        return $this->findByEvenement($moduleFourId);
    }
}
