<?php

namespace App\Repository;

use App\Entity\Consultation;
use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

    public function countDistinctPatientsSeenBetween(
        Medecin $medecin,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): int {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT p.id)')
            ->join('c.dossierMedical', 'dm')
            ->join('dm.patient', 'p')
            ->andWhere('c.medecin = :medecin')
            ->andWhere('c.date >= :start')
            ->andWhere('c.date < :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int,array{dayKey:string,total:int}>
     */
    public function countConsultationsByDayBetween(
        Medecin $medecin,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        $rows = $this->createQueryBuilder('c')
            ->select('SUBSTRING(c.date, 1, 10) AS dayKey')
            ->addSelect('COUNT(c.id) AS total')
            ->andWhere('c.medecin = :medecin')
            ->andWhere('c.date >= :start')
            ->andWhere('c.date < :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('dayKey')
            ->orderBy('dayKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'dayKey' => isset($row['dayKey']) ? (string) $row['dayKey'] : '',
                'total' => isset($row['total']) ? (int) $row['total'] : 0,
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array{monthKey:string,total:int}>
     */
    public function countConsultationsByMonthBetween(
        Medecin $medecin,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        $rows = $this->createQueryBuilder('c')
            ->select('SUBSTRING(c.date, 1, 7) AS monthKey')
            ->addSelect('COUNT(c.id) AS total')
            ->andWhere('c.medecin = :medecin')
            ->andWhere('c.date >= :start')
            ->andWhere('c.date < :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('monthKey')
            ->orderBy('monthKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'monthKey' => isset($row['monthKey']) ? (string) $row['monthKey'] : '',
                'total' => isset($row['total']) ? (int) $row['total'] : 0,
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array{motif:string,total:int}>
     */
    public function findMotifDistributionBetween(
        Medecin $medecin,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        int $limit = 8
    ): array {
        $limit = max(1, $limit);

        // Classification des motifs via CASE DQL sur le texte du diagnostic.
        $caseExpression = <<<DQL
CASE
    WHEN c.diagnostic IS NULL OR c.diagnostic = '' THEN 'Non renseigne'
    WHEN LOWER(c.diagnostic) LIKE :motif_cardio OR LOWER(c.diagnostic) LIKE :motif_tension THEN 'Cardiologie'
    WHEN LOWER(c.diagnostic) LIKE :motif_diabete OR LOWER(c.diagnostic) LIKE :motif_thyroide THEN 'Endocrinologie'
    WHEN LOWER(c.diagnostic) LIKE :motif_infection OR LOWER(c.diagnostic) LIKE :motif_grippe OR LOWER(c.diagnostic) LIKE :motif_fievre THEN 'Infection'
    WHEN LOWER(c.diagnostic) LIKE :motif_douleur OR LOWER(c.diagnostic) LIKE :motif_migraine THEN 'Douleur'
    WHEN LOWER(c.diagnostic) LIKE :motif_digestif OR LOWER(c.diagnostic) LIKE :motif_estomac THEN 'Digestif'
    ELSE 'Autres'
END
DQL;

        $rows = $this->createQueryBuilder('c')
            ->select($caseExpression . ' AS motif')
            ->addSelect('COUNT(c.id) AS total')
            ->andWhere('c.medecin = :medecin')
            ->andWhere('c.date >= :start')
            ->andWhere('c.date < :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('motif_cardio', '%cardio%')
            ->setParameter('motif_tension', '%tension%')
            ->setParameter('motif_diabete', '%diab%')
            ->setParameter('motif_thyroide', '%thyro%')
            ->setParameter('motif_infection', '%infect%')
            ->setParameter('motif_grippe', '%grippe%')
            ->setParameter('motif_fievre', '%fievre%')
            ->setParameter('motif_douleur', '%douleur%')
            ->setParameter('motif_migraine', '%migraine%')
            ->setParameter('motif_digestif', '%digest%')
            ->setParameter('motif_estomac', '%estomac%')
            ->groupBy('motif')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'motif' => isset($row['motif']) ? (string) $row['motif'] : 'Autres',
                'total' => isset($row['total']) ? (int) $row['total'] : 0,
            ];
        }

        return $result;
    }
}
