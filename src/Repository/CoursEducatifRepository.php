<?php

namespace App\Repository;

use App\Entity\CoursEducatif;
use App\Entity\CategorieSante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoursEducatif>
 */
class CoursEducatifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoursEducatif::class);
    }

    /**
     * Find courses by category
     */
    public function findByCategorie(CategorieSante $categorie): array
    {
<<<<<<< HEAD
        return $this->createQueryBuilder('c')
            ->andWhere('c.categorieSante = :categorie')
            ->setParameter('categorie', $categorie)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
=======
        // Use native SQL to avoid UUID conversion issues
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM cours_educatif WHERE categorie_sante_id = ? ORDER BY date_creation DESC';
        
        $result = $conn->executeQuery($sql, [$categorie->getId()->toBinary()]);
        
        $coursData = $result->fetchAllAssociative();
        
        // Convert to entities
        $cours = [];
        foreach ($coursData as $data) {
            $coursEntity = $this->find($data['id']);
            if ($coursEntity) {
                $cours[] = $coursEntity;
            }
        }
        
        return $cours;
>>>>>>> isramedi
    }

    /**
     * Find recent courses
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search courses by title or content
     */
    public function search(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.titre LIKE :search OR c.contenu LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find courses with questions count
     */
    public function findWithQuestionsCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(q.id) as questionsCount')
            ->leftJoin('c.questions', 'q')
            ->groupBy('c.id')
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find courses validated by a specific doctor
     */
    public function findValidatedByMedecin($medecinId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.medecinValidateur = :medecin')
            ->setParameter('medecin', $medecinId)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
