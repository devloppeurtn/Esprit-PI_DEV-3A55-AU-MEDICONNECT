<?php

namespace App\Repository;

use App\Entity\EventFeedback;
use App\Entity\Evenement;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventFeedback>
 */
class EventFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventFeedback::class);
    }

    /**
     * Trouve le feedback d'un participant pour un événement
     */
    public function findByParticipantAndEvent(Participant $participant, Evenement $evenement): ?EventFeedback
    {
        return $this->createQueryBuilder('f')
            ->where('f.participant = :participant')
            ->andWhere('f.evenement = :evenement')
            ->setParameter('participant', $participant)
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les feedbacks d'un événement
     */
    public function findByEvent(Evenement $evenement): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la note moyenne d'un événement
     */
    public function getAverageRating(Evenement $evenement): float
    {
        $result = $this->createQueryBuilder('f')
            ->select('AVG(f.rating) as avgRating')
            ->where('f.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float)$result, 1) : 0.0;
    }

    /**
     * Compte le nombre de feedbacks pour un événement
     */
    public function countByEvent(Evenement $evenement): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre de feedbacks par note pour un événement
     */
    public function getRatingDistribution(Evenement $evenement): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('f.rating, COUNT(f.id) as count')
            ->where('f.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->groupBy('f.rating')
            ->orderBy('f.rating', 'DESC')
            ->getQuery()
            ->getResult();

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($results as $result) {
            $distribution[$result['rating']] = (int)$result['count'];
        }

        return $distribution;
    }
}
