<?php

namespace App\Repository;

use App\Entity\ReponseUtilisateur;
use App\Entity\Utilisateur;
use App\Entity\CoursEducatif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReponseUtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReponseUtilisateur::class);
    }

    /**
     * Find user responses for a specific course
     */
    public function findByUtilisateurAndCours(Utilisateur $utilisateur, CoursEducatif $cours): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.question', 'q')
            ->where('r.utilisateur = :utilisateur')
            ->andWhere('q.coursEducatif = :cours')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('cours', $cours)
            ->orderBy('r.dateReponse', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for a user on a specific course
     */
    public function getStatistiques(Utilisateur $utilisateur, CoursEducatif $cours): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as totalReponses')
            ->addSelect('SUM(CASE WHEN r.estCorrecte = true THEN 1 ELSE 0 END) as bonnesReponses')
            ->addSelect('SUM(r.pointsObtenus) as totalPoints')
            ->join('r.question', 'q')
            ->where('r.utilisateur = :utilisateur')
            ->andWhere('q.coursEducatif = :cours')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('cours', $cours);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Find all responses for a course, grouped by user
     * Returns an array where keys are user IDs and values are arrays of responses
     */
    public function findByCoursGroupedByUtilisateur(CoursEducatif $cours): array
    {
        $reponses = $this->createQueryBuilder('r')
            ->join('r.question', 'q')
            ->join('r.utilisateur', 'u')
            ->where('q.coursEducatif = :cours')
            ->setParameter('cours', $cours)
            ->orderBy('r.dateReponse', 'DESC')
            ->addOrderBy('u.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();

        // Group by user
        $grouped = [];
        foreach ($reponses as $reponse) {
            $userId = $reponse->getUtilisateur()->getId()->toRfc4122();
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'utilisateur' => $reponse->getUtilisateur(),
                    'reponses' => [],
                    'stats' => [
                        'total' => 0,
                        'correctes' => 0,
                        'points' => 0,
                        'dateDerniereTentative' => null,
                    ]
                ];
            }
            $grouped[$userId]['reponses'][] = $reponse;
            $grouped[$userId]['stats']['total']++;
            if ($reponse->isEstCorrecte()) {
                $grouped[$userId]['stats']['correctes']++;
            }
            $grouped[$userId]['stats']['points'] += $reponse->getPointsObtenus();
            
            // Track latest attempt date
            $dateReponse = $reponse->getDateReponse();
            if (!$grouped[$userId]['stats']['dateDerniereTentative'] || 
                $dateReponse > $grouped[$userId]['stats']['dateDerniereTentative']) {
                $grouped[$userId]['stats']['dateDerniereTentative'] = $dateReponse;
            }
        }

        return $grouped;
    }
}
