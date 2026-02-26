<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Evenement;
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
    public function findByEvenement(Evenement $evenement): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :e')
            ->setParameter('e', $evenement)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
