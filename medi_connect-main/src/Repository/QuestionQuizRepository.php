<?php

namespace App\Repository;

use App\Entity\QuestionQuiz;
use App\Entity\CoursEducatif;
use App\Enum\StatutQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionQuiz>
 */
class QuestionQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionQuiz::class);
    }

    /**
     * Find questions by course
     */
    public function findByCoursEducatif(CoursEducatif $cours): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.coursEducatif = :cours')
            ->setParameter('cours', $cours)
            ->orderBy('q.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find validated questions by course
     */
    public function findValidatedByCoursEducatif(CoursEducatif $cours): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.coursEducatif = :cours')
            ->andWhere('q.statut = :statut')
            ->setParameter('cours', $cours)
            ->setParameter('statut', StatutQuestion::VALIDE_MEDECIN)
            ->orderBy('q.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find questions by status
     */
    public function findByStatut(StatutQuestion $statut): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('q.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find questions pending validation
     */
    public function findPendingValidation(): array
    {
        return $this->findByStatut(StatutQuestion::IA_PROPOSE);
    }

    /**
     * Get random questions for a quiz
     */
    public function getRandomQuestionsForCoursEducatif(CoursEducatif $cours, int $limit = 10): array
    {
        $questions = $this->findValidatedByCoursEducatif($cours);
        
        if (count($questions) <= $limit) {
            return $questions;
        }

        shuffle($questions);
        return array_slice($questions, 0, $limit);
    }

    /**
     * Count questions by status for a course
     */
    public function countByStatutForCours(CoursEducatif $cours, StatutQuestion $statut): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.coursEducatif = :cours')
            ->andWhere('q.statut = :statut')
            ->setParameter('cours', $cours)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
