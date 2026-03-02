<?php

namespace App\Repository;

use App\Entity\Participant;
<<<<<<< HEAD
use App\Entity\Evenement;
=======
<<<<<<< HEAD
=======
use App\Entity\Evenement;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
=======
<<<<<<< HEAD
    public function findByEvenement($evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :m')
            ->setParameter('m', $evenementId)
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function findByEvenement(Evenement $evenement): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :e')
            ->setParameter('e', $evenement)
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
<<<<<<< HEAD
=======
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
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
}
