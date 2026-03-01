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
}
