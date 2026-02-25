<?php

namespace App\Repository;

use App\Entity\Participant;
<<<<<<< HEAD
=======
use App\Entity\Evenement;
>>>>>>> isramedi
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
<<<<<<< HEAD
    public function findByEvenement($evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :m')
            ->setParameter('m', $evenementId)
=======
    public function findByEvenement(Evenement $evenement): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :e')
            ->setParameter('e', $evenement)
>>>>>>> isramedi
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
<<<<<<< HEAD

    /**
     * Backwards compatibility wrapper
     * @return Participant[]
     */
    public function findByModuleFour($moduleFourId): array
    {
        return $this->findByEvenement($moduleFourId);
    }
=======
>>>>>>> isramedi
}
